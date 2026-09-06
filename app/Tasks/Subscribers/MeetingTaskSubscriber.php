<?php

namespace App\Tasks\Subscribers;

use App\Actions\ResolveMeetingNotificationAudience;
use App\Actions\ResolveTaskAssignees;
use App\Events\MeetingFullyCreated;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\User;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class MeetingTaskSubscriber
{
    public function __construct(
        protected AgendaCreationTaskHandler $creationHandler,
        protected AgendaCompletionTaskHandler $completionHandler,
        protected PeriodicityGapTaskHandler $periodicityGapHandler,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MeetingFullyCreated::class,
            [self::class, 'handleMeetingCreated']
        );

        $events->listen(
            'eloquent.saved: '.AgendaItem::class,
            [self::class, 'handleAgendaItemSaved']
        );

        $events->listen(
            'eloquent.created: '.AgendaItem::class,
            [self::class, 'handleAgendaItemCreated']
        );

        $events->listen(
            'eloquent.deleted: '.AgendaItem::class,
            [self::class, 'handleAgendaItemDeleted']
        );
    }

    public function handleMeetingCreated(MeetingFullyCreated $event): void
    {
        $meeting = $event->meeting;

        $meeting->load(['institutions.tenant', 'agendaItems']);

        // Nominated administrators when the term has any, else the representatives who
        // were actually active at the meeting date. See ResolveTaskAssignees.
        $representatives = ResolveTaskAssignees::forMeeting($meeting);

        if ($representatives->isNotEmpty() && $meeting->agendaItems->isEmpty()) {
            $this->creationHandler->findOrCreate(
                name: __('Sukurti posėdžio darbotvarkės klausimus'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->addDays(3)->toDateString(),
            );
        }

        if ($representatives->isNotEmpty() && $meeting->agendaItems->isNotEmpty()) {
            $this->completionHandler->findOrCreate(
                name: __('Užpildyti darbotvarkės klausimų informaciją'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->toDateString(),
            );
        }

        foreach ($meeting->institutions as $institution) {
            $this->periodicityGapHandler->completeForInstitution(
                institution: $institution,
                reason: __('tasks.periodicity_gap.completed_meeting_created'),
            );
        }

        $recipients = ResolveMeetingNotificationAudience::execute($meeting);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MeetingCreatedNotification($meeting));
        }
    }

    public function handleAgendaItemSaved(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        $meeting->load('agendaItems.votes');

        /** @var User|null $actor */
        $actor = Auth::user();

        $this->completionHandler->reopenIfNeeded($meeting);
        $this->ensureCompletionTaskExists($meeting);

        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting, $actor);

        if ($wasCompleted) {
            $this->notifyAudienceOfCompletion($meeting, $actor);
        }
    }

    /**
     * Handle agenda item created event.
     * Completes creation task and creates/updates completion task.
     */
    public function handleAgendaItemCreated(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        $meeting->load('agendaItems');

        /** @var User|null $actor */
        $actor = Auth::user();

        $this->creationHandler->completeForMeeting($meeting, $actor);

        $existingCompletionTask = $this->completionHandler->findExistingTask($meeting);

        if ($existingCompletionTask) {
            $this->completionHandler->updateProgressForMeeting($meeting, $actor);
        } else {
            $representatives = ResolveTaskAssignees::forMeeting($meeting);

            if ($representatives->isNotEmpty()) {
                $this->completionHandler->findOrCreate(
                    name: __('Užpildyti darbotvarkės klausimų informaciją'),
                    meeting: $meeting,
                    users: $representatives,
                    dueDate: $meeting->start_time->addDays(7)->toDateString(),
                );
            }
        }
    }

    public function handleAgendaItemDeleted(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        // The meeting is gone when the agenda item is being removed as part of the
        // meeting's own permanent deletion, and also whenever the meeting is trashed.
        // There is no progress left to recalculate in either case.
        if ($meeting === null) {
            return;
        }

        $meeting->load('agendaItems');

        /** @var User|null $actor */
        $actor = Auth::user();

        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting, $actor);

        if ($wasCompleted) {
            $this->notifyAudienceOfCompletion($meeting, $actor);
        }
    }

    protected function notifyAudienceOfCompletion(Meeting $meeting, ?User $completedBy = null): void
    {
        $meeting->load(['institutions.tenant']);

        $recipients = ResolveMeetingNotificationAudience::execute($meeting);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MeetingAgendaCompletedNotification($meeting, $completedBy));
        }
    }

    protected function ensureCompletionTaskExists(Meeting $meeting): void
    {
        $existingTask = $this->completionHandler->findExistingTask($meeting);

        if ($existingTask) {
            return;
        }

        // reopenIfNeeded would have handled any completed tasks that need reopening.
        $hasCompletedTask = Task::query()
            ->where('taskable_type', MorphMap::alias(Meeting::class))
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCompletion)
            ->whereNotNull('completed_at')
            ->exists();

        if ($hasCompletedTask) {
            return;
        }

        $representatives = ResolveTaskAssignees::forMeeting($meeting);

        if ($representatives->isNotEmpty()) {
            $this->completionHandler->findOrCreate(
                name: __('Užpildyti darbotvarkės klausimų informaciją'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->addDays(7)->toDateString(),
            );
        }
    }
}
