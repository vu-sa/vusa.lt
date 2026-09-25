<?php

namespace App\Notifications;

use App\Actions\GetInstitutionCoordinators;
use App\Enums\EmailDelivery;
use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Enums\NotificationUrgency;
use App\Models\Institution;
use App\Models\User;
use App\Support\QuietHours;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * One content contract (title, body, primaryAction, context) rendered in-app, by email and as push.
 * Subclasses declare their NotificationType; category, urgency and channel defaults follow from it,
 * and the recipient's per-type preferences decide delivery in via().
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function type(): NotificationType;

    public function category(): NotificationCategory
    {
        return $this->type()->section();
    }

    public function urgency(): NotificationUrgency
    {
        return $this->type()->urgency();
    }

    /**
     * Get the notification title.
     */
    abstract public function title(object $notifiable): string;

    /**
     * Get the notification body.
     */
    abstract public function body(object $notifiable): string;

    /**
     * Get the URL to navigate to.
     */
    abstract public function url(): string;

    /**
     * Get the emoji/icon for the notification.
     */
    public function icon(): string
    {
        return match ($this->category()) {
            NotificationCategory::Comment => '💬',
            NotificationCategory::Task => '☑️',
            NotificationCategory::Reservation => '📅',
            NotificationCategory::Meeting => '🗓️',
            NotificationCategory::Registration => '📝',
            NotificationCategory::User => '👤',
            NotificationCategory::Duty => '🎯',
            NotificationCategory::System => '🔔',
        };
    }

    /**
     * Get the ModelEnum key for the related model (for icon display on frontend).
     * Return null to use the category's default icon.
     */
    public function modelClass(): ?string
    {
        return null;
    }

    /**
     * The one action this notification asks for; null when it only reports something.
     *
     * @return array{label: string, url: string}|null
     */
    public function primaryAction(): ?array
    {
        return null;
    }

    /**
     * A second action, only when the answer is binary.
     *
     * @return array{label: string, url: string}|null
     */
    public function secondaryAction(): ?array
    {
        return null;
    }

    /**
     * Whether the secondary action answers the same question as the primary one (R-a), so a mail
     * draws it as a second button rather than a text link.
     */
    public function secondaryActionIsAnswer(): bool
    {
        return false;
    }

    /**
     * Label/value rows saying what this is about (institution, date, deadline); keep to four.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function context(object $notifiable): array
    {
        return [];
    }

    /**
     * Turn `notifications.context.*` key => value pairs into context() rows, dropping blank values
     * so an absent relation never renders an empty row.
     *
     * @param  array<string, string|int|null>  $rows
     * @return array<int, array{label: string, value: string}>
     */
    protected function contextRows(array $rows): array
    {
        $context = [];

        foreach ($rows as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $context[] = ['label' => __('notifications.context.'.$key), 'value' => (string) $value];
        }

        return $context;
    }

    /**
     * @deprecated Superseded by primaryAction()/secondaryAction(); remove once stored notification
     *             rows written before them have aged out. Kept so those rows keep their shape.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function actions(): array
    {
        return array_values(array_filter([$this->primaryAction(), $this->secondaryAction()]));
    }

    /**
     * Get the subject (actor) information.
     *
     * @return array{modelClass: string, name: string, image?: string}|null
     */
    public function subject(): ?array
    {
        return null;
    }

    /**
     * Get the object (target) information.
     *
     * @return array{modelClass: string, name: string, url: string, id?: string|int}|null
     */
    public function object(): ?array
    {
        return null;
    }

    /**
     * The person the email is signed by, or null to sign as Mano VU SA.
     *
     * @return array{name: string, duty: string|null, email: string}|null
     */
    public function mailSignature(object $notifiable): ?array
    {
        return null;
    }

    /**
     * Sign as the coordinator of an institution, using the duty address so a reply reaches the role.
     *
     * @return array{name: string, duty: string|null, email: string}|null
     */
    protected function coordinatorSignature(object $notifiable, ?Institution $institution): ?array
    {
        if ($institution === null) {
            return null;
        }

        $coordinator = GetInstitutionCoordinators::execute([$institution], $notifiable instanceof User ? $notifiable : null)[0] ?? null;

        if ($coordinator === null || $coordinator['email'] === null) {
            return null;
        }

        return [
            'name' => $coordinator['name'],
            'duty' => $coordinator['duty'],
            'email' => $coordinator['email'],
        ];
    }

    /**
     * Push is held until 07:00 during quiet hours; in-app and email are never delayed.
     */
    public function withDelay(object $notifiable, string $channel): ?CarbonInterface
    {
        if ($channel === WebPushChannel::class && QuietHours::isQuiet(now())) {
            return QuietHours::nextEnd(now());
        }

        return null;
    }

    /**
     * In-app always, so the bell is the full record even while muted; email and push follow the
     * recipient's choice for this type. Locked role-inbox mail is sent regardless.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (! $notifiable instanceof User) {
            return $channels;
        }

        $type = $this->type();
        $muted = $notifiable->isGloballyMuted();

        if (! $muted && $notifiable->wantsPushFor($type)) {
            $channels[] = WebPushChannel::class;
        }

        if ($type->lockedEmail() === EmailDelivery::Immediate
            || (! $muted && $notifiable->emailDeliveryFor($type) === EmailDelivery::Immediate)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification (for database storage).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category()->value,
            'type' => $this->type()->value,
            'modelClass' => $this->modelClass() ?? $this->category()->modelEnumKey(),
            'title' => $this->title($notifiable),
            'body' => $this->body($notifiable),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'color' => $this->category()->color(),
            'primaryAction' => $this->primaryAction(),
            'secondaryAction' => $this->secondaryAction(),
            'context' => $this->context($notifiable),
            'actions' => $this->actions(),
            'subject' => $this->subject(),
            'object' => $this->object(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the mail representation of the notification: the same contract the in-app list renders.
     */
    public function toMail(object $notifiable): MailMessage|Mailable
    {
        $action = $this->primaryAction() ?? ['label' => __('Peržiūrėti'), 'url' => $this->url()];

        return (new MailMessage)
            ->subject(Str::limit($this->title($notifiable), 59, '…'))
            ->action($action['label'], $action['url'])
            ->markdown('emails.notification', [
                'title' => $this->title($notifiable),
                'body' => $this->body($notifiable),
                'context' => $this->context($notifiable),
                'secondaryAction' => $this->secondaryAction(),
                'secondaryIsAnswer' => $this->secondaryActionIsAnswer(),
                'signature' => $this->mailSignature($notifiable),
                'category' => __($this->category()->labelKey()),
                'settingsUrl' => route('profile.notifications'),
            ]);
    }

    /**
     * Get the Web Push representation of the notification: one line, one action, one deep link.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $action = $this->primaryAction() ?? ['label' => __('Peržiūrėti'), 'url' => $this->url()];

        return (new WebPushMessage)
            ->title($this->title($notifiable))
            ->icon('/images/icons/favicons/favicon-196x196.png')
            ->body(Str::limit(strip_tags($this->body($notifiable)), 120))
            ->action($action['label'], 'view')
            // Android defers normal-urgency pushes while the phone idles (Doze); an ask should not wait.
            ->options($this->urgency() === NotificationUrgency::Act
                ? ['TTL' => 86400, 'urgency' => 'high']
                : ['TTL' => 3600, 'urgency' => 'normal'])
            ->data(['url' => $action['url']]);
    }

    /**
     * Get data for email digest grouping.
     *
     * @return array{category: string, title: string, body: string, url: string, icon: string, context: array<int, array{label: string, value: string}>, primaryAction: array{label: string, url: string}|null}
     */
    public function toDigestItem(object $notifiable): array
    {
        return [
            'category' => $this->category()->value,
            'title' => $this->title($notifiable),
            'body' => Str::limit(strip_tags($this->body($notifiable)), 200),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'context' => $this->context($notifiable),
            'primaryAction' => $this->primaryAction(),
        ];
    }
}
