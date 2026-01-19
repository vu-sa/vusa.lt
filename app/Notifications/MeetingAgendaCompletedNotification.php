<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Meeting;
use App\Models\User;

/**
 * Notification sent to administrators when all meeting agenda items are completed.
 */
class MeetingAgendaCompletedNotification extends BaseNotification
{
    protected Meeting $meeting;

    protected ?User $completedBy;

    /**
     * Create a new notification instance.
     *
     * @param  User|null  $completedBy  The user who completed the last agenda item
     */
    public function __construct(Meeting $meeting, ?User $completedBy = null)
    {
        $this->meeting = $meeting;
        $this->completedBy = $completedBy;
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

        if ($this->completedBy) {
            return __('notifications.meeting_agenda_completed_body_with_user', [
                'institution' => $institutionName,
                'count' => $agendaItemCount,
                'user' => $this->completedBy->name,
            ]);
        }

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

    public function subject(): ?array
    {
        if ($this->completedBy) {
            return [
                'modelClass' => 'User',
                'name' => $this->completedBy->name,
                'image' => $this->completedBy->profile_photo_path,
            ];
        }

        return null;
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
