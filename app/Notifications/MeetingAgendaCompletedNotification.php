<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Meeting;

/**
 * Notification sent to administrators when all meeting agenda items are completed.
 */
class MeetingAgendaCompletedNotification extends BaseNotification
{
    protected Meeting $meeting;

    /**
     * Create a new notification instance.
     */
    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Meeting;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.meeting_agenda_completed_title');
    }

    public function body(object $notifiable): string
    {
        $institutionName = $this->meeting->institutions->first()?->name ?? __('Nežinoma institucija');
        $agendaItemCount = $this->meeting->agendaItems()->count();

        return __('notifications.meeting_agenda_completed_body', [
            'institution' => $institutionName,
            'count' => $agendaItemCount,
        ]);
    }

    public function url(): string
    {
        return route('meetings.show', $this->meeting->id);
    }

    public function icon(): string
    {
        return '✅';
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
}
