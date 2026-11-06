<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Notifications\Concerns\DescribesMeeting;
use App\Notifications\Concerns\ReachesFollowers;

/**
 * Notification sent to overseers and followers when a new meeting is created.
 */
class MeetingCreatedNotification extends BaseNotification
{
    use DescribesMeeting;
    use ReachesFollowers;

    public function type(): NotificationType
    {
        return $this->viaFollow ? NotificationType::FollowedInstitutionActivity : NotificationType::MeetingCreated;
    }

    public function __construct(protected Meeting $meeting) {}

    public function title(object $notifiable): string
    {
        return __('notifications.meeting_created_title', ['institution' => $this->object()['name']]);
    }

    public function body(object $notifiable): string
    {
        return __($this->meeting->start_time->isPast() ? 'notifications.meeting_created_body_past' : 'notifications.meeting_created_body', [
            'meeting' => $this->meetingPhrase($this->meeting),
            // "15.00 val." already ends the sentence.
            'date' => rtrim($this->meetingDate($this->meeting), '.'),
        ]);
    }

    #[\Override]
    public function digestLine(object $notifiable): string
    {
        return ($this->meeting->institutions->first()->name ?? __('Nežinoma institucija')).', '.$this->meeting->start_time->format('m-d');
    }

    #[\Override]
    public function digestGroupTitle(object $notifiable): string
    {
        return __('notifications.meeting_created_digest_group');
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
