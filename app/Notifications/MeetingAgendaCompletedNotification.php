<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Meeting;
use App\Models\User;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Illuminate\Support\Arr;

/**
 * Notification sent to administrators when all meeting agenda items are completed.
 */
class MeetingAgendaCompletedNotification extends BaseNotification
{
    /**
     * Create a new notification instance.
     *
     * @param  User|null  $completedBy  The user who completed the last agenda item
     */
    public function __construct(protected Meeting $meeting, protected ?User $completedBy = null) {}

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
        $institutionName = $this->meeting->institutions->first()->name ?? __('Nežinoma institucija');

        // Get type counts for the summary
        $handler = app(AgendaCompletionTaskHandler::class);
        $typeCounts = $handler->getAgendaItemTypeCounts($this->meeting);

        // Every typed item, so adding a type never silently shrinks the reported total.
        $totalCount = array_sum(Arr::except($typeCounts, 'unset'));

        // Build the body message
        if ($this->completedBy) {
            $body = __('notifications.meeting_agenda_completed_body_with_user', [
                'institution' => $institutionName,
                'count' => $totalCount,
                'user' => $this->completedBy->name,
            ]);
        } else {
            $body = __('notifications.meeting_agenda_completed_body', [
                'institution' => $institutionName,
                'count' => $totalCount,
            ]);
        }

        // Add type breakdown summary
        $summary = $this->buildTypeSummary($typeCounts);
        if ($summary) {
            $body .= ' '.$summary;
        }

        // Add note about additional votes if there were voting items
        if ($typeCounts['voting'] > 0) {
            $body .= ' '.__('notifications.meeting_agenda_additional_votes_note');
        }

        return $body;
    }

    /**
     * Build a summary string of agenda item types.
     */
    protected function buildTypeSummary(array $typeCounts): string
    {
        $parts = [];

        if ($typeCounts['voting'] > 0) {
            $parts[] = __('notifications.meeting_agenda_type_voting', ['count' => $typeCounts['voting']]);
        }
        if ($typeCounts['informational'] > 0) {
            $parts[] = __('notifications.meeting_agenda_type_informational', ['count' => $typeCounts['informational']]);
        }
        if ($typeCounts['deferred'] > 0) {
            $parts[] = __('notifications.meeting_agenda_type_deferred', ['count' => $typeCounts['deferred']]);
        }
        if ($typeCounts['break'] > 0) {
            $parts[] = __('notifications.meeting_agenda_type_break', ['count' => $typeCounts['break']]);
        }

        if (empty($parts)) {
            return '';
        }

        return '('.implode(', ', $parts).')';
    }

    public function url(): string
    {
        return route('meetings.show', $this->meeting->id);
    }

    #[\Override]
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
            'name' => $this->meeting->institutions->first()->name ?? __('Susitikimas'),
            'url' => $this->url(),
            'id' => $this->meeting->id,
        ];
    }

    #[\Override]
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
