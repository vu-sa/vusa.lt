<?php

namespace App\Notifications;

use App\Enums\AgendaItemType;
use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\Concerns\DescribesMeeting;
use App\Notifications\Concerns\ReachesFollowers;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Notification sent to overseers and followers when all meeting agenda items are completed.
 */
class MeetingAgendaCompletedNotification extends BaseNotification
{
    use DescribesMeeting;
    use ReachesFollowers;

    public function type(): NotificationType
    {
        return $this->viaFollow ? NotificationType::FollowedInstitutionActivity : NotificationType::MeetingAgendaCompleted;
    }

    /**
     * Create a new notification instance.
     *
     * @param  User|null  $completedBy  The user who completed the last agenda item
     */
    public function __construct(protected Meeting $meeting, protected ?User $completedBy = null) {}

    public function title(object $notifiable): string
    {
        return __('notifications.meeting_agenda_completed_title', ['institution' => $this->object()['name']]);
    }

    public function body(object $notifiable): string
    {
        $handler = app(AgendaCompletionTaskHandler::class);
        $typeCounts = $handler->getAgendaItemTypeCounts($this->meeting);

        // Every typed item, so adding a type never silently shrinks the reported total.
        $totalCount = array_sum(Arr::except($typeCounts, 'unset'));

        $items = $this->completedBy
            ? trans_choice('notifications.meeting_agenda_items_accusative', $totalCount)
            : trans_choice('notifications.meeting_agenda_items', $totalCount);

        $summary = $this->buildTypeSummary($typeCounts);
        if ($summary !== '') {
            $items .= ' ('.$summary.')';
        }

        $body = $this->completedBy
            ? __('notifications.meeting_agenda_completed_body_with_user', ['items' => $items, 'user' => $this->completedBy->name])
            : __('notifications.meeting_agenda_completed_body', ['items' => $items]);

        if ($typeCounts['voting'] > 0) {
            $body .= ' '.__('notifications.meeting_agenda_additional_votes_note');
        }

        return $body;
    }

    /** "balsavimas: 3, aptarimas: 1" — the type labels the agenda shows, so no count agreement is needed. */
    protected function buildTypeSummary(array $typeCounts): string
    {
        $locale = app()->getLocale();

        return collect(Arr::except($typeCounts, 'unset'))
            ->filter(fn (int $count): bool => $count > 0)
            ->map(fn (int $count, string $type): string => Str::lcfirst(AgendaItemType::from($type)->label($locale)).': '.$count)
            ->implode(', ');
    }

    #[\Override]
    public function digestLine(object $notifiable): string
    {
        return $this->meeting->institutions->first()->name ?? __('Nežinoma institucija');
    }

    #[\Override]
    public function digestGroupTitle(object $notifiable): string
    {
        return __('notifications.meeting_agenda_completed_digest_group');
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
    public function context(object $notifiable): array
    {
        return $this->contextRows(['date' => $this->meetingDate($this->meeting)]);
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
