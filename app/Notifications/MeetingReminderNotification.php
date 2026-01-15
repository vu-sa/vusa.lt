<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Meeting;

/**
 * Notification sent to remind users about upcoming meetings.
 */
class MeetingReminderNotification extends BaseNotification
{
    protected Meeting $meeting;

    protected int $hoursUntil;

    /**
     * Create a new notification instance.
     */
    public function __construct(Meeting $meeting, int $hoursUntil)
    {
        $this->meeting = $meeting;
        $this->hoursUntil = $hoursUntil;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Meeting;
    }

    public function title(object $notifiable): string
    {
        if ($this->hoursUntil <= 2) {
            return __('notifications.meeting_reminder_soon_title');
        }

        return __('notifications.meeting_reminder_title');
    }

    public function body(object $notifiable): string
    {
        $meetingName = $this->meeting->institutions->first()?->name ?? __('Susitikimas');

        if ($this->hoursUntil <= 1) {
            return __('notifications.meeting_reminder_body_one_hour', [
                'meeting' => $meetingName,
            ]);
        }

        return __('notifications.meeting_reminder_body', [
            'meeting' => $meetingName,
            'hours' => $this->hoursUntil,
        ]);
    }

    public function url(): string
    {
        return route('meetings.show', $this->meeting->id);
    }

    public function icon(): string
    {
        return $this->hoursUntil <= 2 ? '⏰' : '🗓️';
    }

    public function modelClass(): ?string
    {
        return 'MEETING';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Meeting',
            'name' => $this->meeting->institutions->first()?->name ?? __('Susitikimas'),
            'url' => $this->url(),
            'id' => $this->meeting->id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_meeting'),
                'url' => $this->url(),
            ],
        ];
    }

    /**
     * Meeting reminders are time-sensitive and should not be digested.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
