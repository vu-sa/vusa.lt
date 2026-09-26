<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Notifications\Concerns\ReachesFollowers;

/**
 * Notification sent to administrators when a new meeting is created.
 */
class MeetingCreatedNotification extends BaseNotification
{
    use ReachesFollowers;

    public function type(): NotificationType
    {
        return $this->viaFollow ? NotificationType::FollowedInstitutionActivity : NotificationType::MeetingCreated;
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Meeting $meeting) {}

    public function title(object $notifiable): string
    {
        return __('notifications.meeting_created_title');
    }

    public function body(object $notifiable): string
    {
        $institutionName = $this->meeting->institutions->first()->name ?? __('Nežinoma institucija');
        $meetingDate = $this->meeting->start_time->format('Y-m-d H:i');

        return __('notifications.meeting_created_body', [
            'institution' => $institutionName,
            'date' => $meetingDate,
        ]);
    }

    public function url(): string
    {
        return route('meetings.show', $this->meeting->id);
    }

    #[\Override]
    public function icon(): string
    {
        return '🗓️';
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
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_meeting'),
            'url' => $this->url(),
        ];
    }
}
