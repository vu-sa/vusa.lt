<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Calendar;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Notification sent to remind users about upcoming calendar events.
 * This notification is opt-in (disabled by default).
 */
class CalendarReminderNotification extends BaseNotification
{
    protected Calendar $calendarEvent;

    protected int $hoursUntil;

    /**
     * Create a new notification instance.
     */
    public function __construct(Calendar $calendarEvent, int $hoursUntil)
    {
        $this->calendarEvent = $calendarEvent;
        $this->hoursUntil = $hoursUntil;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Calendar;
    }

    public function title(object $notifiable): string
    {
        if ($this->hoursUntil <= 2) {
            return __('notifications.calendar_reminder_soon_title');
        }

        return __('notifications.calendar_reminder_title');
    }

    public function body(object $notifiable): string
    {
        $eventTitle = $this->calendarEvent->title;
        $location = $this->calendarEvent->location;

        if ($this->hoursUntil <= 1) {
            return __('notifications.calendar_reminder_body_one_hour', [
                'event' => $eventTitle,
                'location' => $location ?? '',
            ]);
        }

        if ($this->hoursUntil >= 24) {
            return __('notifications.calendar_reminder_body_tomorrow', [
                'event' => $eventTitle,
                'location' => $location ?? '',
            ]);
        }

        return __('notifications.calendar_reminder_body', [
            'event' => $eventTitle,
            'hours' => $this->hoursUntil,
            'location' => $location ?? '',
        ]);
    }

    public function url(): string
    {
        // Link to public calendar event page with language prefix
        return url('/lt/kalendorius/renginys/'.$this->calendarEvent->id);
    }

    public function icon(): string
    {
        return $this->hoursUntil <= 2 ? '⏰' : '📆';
    }

    public function modelClass(): ?string
    {
        return 'CALENDAR';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Calendar',
            'name' => $this->calendarEvent->title,
            'url' => $this->url(),
            'id' => $this->calendarEvent->id,
        ];
    }

    /**
     * Do not support email digest for now.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }

    /**
     * Get the notification's delivery channels.
     * Only database, broadcast, and webpush - no email.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check if notifications are globally muted for this user
        if (method_exists($notifiable, 'isGloballyMuted') && $notifiable->isGloballyMuted()) {
            return [];
        }

        // Check if user has calendar notifications enabled
        if (method_exists($notifiable, 'shouldReceiveNotification')) {
            $channels = [];

            if ($notifiable->shouldReceiveNotification($this->category(), NotificationChannel::InApp)) {
                $channels[] = 'database';
                $channels[] = 'broadcast';
            }

            if ($notifiable->shouldReceiveNotification($this->category(), NotificationChannel::Push)) {
                $channels[] = WebPushChannel::class;
            }

            return $channels;
        }

        // Default: database for persistence, broadcast for real-time, webpush for offline
        return ['database', 'broadcast', WebPushChannel::class];
    }
}
