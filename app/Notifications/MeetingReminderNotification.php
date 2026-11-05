<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Notifications\Concerns\NamesObjectInTitle;

/**
 * Notification sent to remind users about upcoming meetings.
 */
class MeetingReminderNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::MeetingReminder;
    }

    public function __construct(protected Meeting $meeting, protected int $hoursUntil) {}

    public function title(object $notifiable): string
    {
        if ($this->hoursUntil <= 2) {
            return __('notifications.meeting_reminder_soon_title', ['meeting' => $this->object()['name']]);
        }

        return __('notifications.meeting_reminder_title', ['meeting' => $this->object()['name']]);
    }

    public function body(object $notifiable): string
    {
        if ($this->hoursUntil <= 1) {
            return __('notifications.meeting_reminder_body_one_hour');
        }

        return __('notifications.meeting_reminder_body', [
            'hours' => $this->hoursUntil,
        ]);
    }

    public function url(): string
    {
        return route('meetings.show', $this->meeting->id);
    }

    #[\Override]
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
            'name' => $this->meeting->institutions->first()->name ?? __('Susitikimas'),
            'url' => $this->url(),
            'id' => $this->meeting->id,
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        $type = $this->meeting->type;

        return $this->contextRows([
            'date' => $this->meeting->start_time->format($type?->isDateOnly() ? 'Y-m-d' : 'Y-m-d H:i'),
            'format' => $type?->label(app()->getLocale()),
        ]);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_meeting'),
            'url' => $this->url(),
        ];
    }
}
