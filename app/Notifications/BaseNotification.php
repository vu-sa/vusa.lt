<?php

namespace App\Notifications;

use App\Actions\GetInstitutionCoordinators;
use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
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
 * Base notification class providing standardized structure for all notifications.
 *
 * All notifications should extend this class and implement:
 * - category(): NotificationCategory - The notification category
 * - title(): string - The notification title (for display and WebPush)
 * - body(): string - The notification body/description
 * - url(): string - The URL to navigate to when clicked
 * - urgency(): NotificationUrgency - How much it asks of the reader; decides email, push and digest
 *
 * Optionally override:
 * - icon(): string - Emoji or icon indicator (default: from category)
 * - modelClass(): ?string - The related model type for icon mapping
 * - primaryAction()/secondaryAction(): ?array - The ask, as {label: string, url: string}
 * - context(): array - Label/value rows [{label: string, value: string}]
 * - subject(): ?array - The actor/subject who triggered the notification
 * - object(): ?array - The object the notification is about
 * - mailSignature(): ?array - The person an email is signed by
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification category.
     */
    abstract public function category(): NotificationCategory;

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
     * How much this notification asks of its reader.
     */
    abstract public function urgency(): NotificationUrgency;

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
     * Determine if this notification is batched into the email digest (derived from urgency).
     */
    public function supportsEmailDigest(): bool
    {
        return $this->urgency()->usesDigest();
    }

    /**
     * Determine if this notification sends a web push (derived from urgency); override where the
     * channel policy (.ai/rules/notifications.md) disagrees with the tier.
     */
    public function sendsPush(): bool
    {
        return $this->urgency()->sendsPush();
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
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check if notifications are globally muted for this user
        if (method_exists($notifiable, 'isGloballyMuted') && $notifiable->isGloballyMuted()) {
            return [];
        }

        // In-app is the record of what happened, so it is never gated; push and email follow the policy.
        $channels = ['database', 'broadcast'];

        if ($this->sendsPush() && $this->wantsPush($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        if ($this->urgency()->sendsImmediateMail() && $this->userWants($notifiable, NotificationChannel::EmailDigest)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Whether the notifiable takes this one as a push; by default, their category setting decides.
     */
    protected function wantsPush(object $notifiable): bool
    {
        return $this->userWants($notifiable, NotificationChannel::Push);
    }

    /**
     * Whether the notifiable allows this category on the channel; non-users (duties) always do.
     */
    protected function userWants(object $notifiable, NotificationChannel $channel): bool
    {
        return ! method_exists($notifiable, 'shouldReceiveNotification')
            || $notifiable->shouldReceiveNotification($this->category(), $channel);
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
                'settingsUrl' => route('profile'),
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
            ->options(['TTL' => $this->urgency() === NotificationUrgency::Act ? 86400 : 3600])
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
