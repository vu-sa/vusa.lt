<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
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
 *
 * Optionally override:
 * - icon(): string - Emoji or icon indicator (default: from category)
 * - modelClass(): ?string - The related model type for icon mapping
 * - primaryAction()/secondaryAction(): ?array - The ask, as {label: string, url: string}
 * - context(): array - Label/value rows [{label: string, value: string}]
 * - subject(): ?array - The actor/subject who triggered the notification
 * - object(): ?array - The object the notification is about
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
            NotificationCategory::News => '📰',
            NotificationCategory::Calendar => '📆',
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
     * @deprecated Superseded by primaryAction()/secondaryAction() in PR 6.1 (.ai/redesign/admin);
     *             slated for removal in Phase 10. Kept so stored notification rows keep their shape.
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
     * Determine if this notification supports email digest.
     * Override to return false for time-sensitive notifications.
     */
    public function supportsEmailDigest(): bool
    {
        return true;
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

        // Default: database for persistence, broadcast for real-time, webpush for offline
        return ['database', 'broadcast', WebPushChannel::class];
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
     * Get the mail representation of the notification.
     * This is used for immediate emails (non-digest) if needed.
     */
    public function toMail(object $notifiable): MailMessage|Mailable
    {
        $action = $this->primaryAction() ?? ['label' => __('Peržiūrėti'), 'url' => $this->url()];

        return (new MailMessage)
            ->subject($this->icon().' '.$this->title($notifiable))
            ->line($this->body($notifiable))
            ->action($action['label'], $action['url']);
    }

    /**
     * Get the Web Push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->icon().' '.$this->title($notifiable))
            ->icon('/images/icons/favicons/favicon-196x196.png')
            ->body(Str::limit(strip_tags($this->body($notifiable)), 100))
            ->action(__('Peržiūrėti'), 'view')
            ->options(['TTL' => 1000])
            ->data(['url' => $this->url()]);

        return $message;
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
