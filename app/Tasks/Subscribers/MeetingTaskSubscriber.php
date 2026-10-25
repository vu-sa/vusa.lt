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
use App\Services\AgendaItemPresenter;
use App\Services\MeetingCompletionService;
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
        protected MeetingCompletionService $completionService,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MeetingFullyCreated::class,
            [self::class, 'handleMeetingCreated']
        );

        $events->listen(
            'eloquent.restored: '.Meeting::class,
            [self::class, 'handleMeetingRestored']
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

        $agendaTask = $this->createAgendaTask($meeting);

        foreach ($meeting->institutions as $institution) {
            $this->periodicityGapHandler->completeForInstitution(
                institution: $institution,
                reason: __('tasks.periodicity_gap.completed_meeting_created'),
            );
        }

        ['overseers' => $overseers, 'followers' => $followers] = ResolveMeetingNotificationAudience::split($meeting);

        // The agenda task itself sends no notice (HandleTaskCreated), so its carriers hear about the meeting here.
        $taskCarriers = $agendaTask?->notifiableUsers() ?? collect();
        $overseers = $overseers->merge($taskCarriers)->unique('id')->values();
        $followers = $followers->reject(fn (User $user): bool => $taskCarriers->contains('id', $user->id));

        /** @var User|null $creator */
        $creator = Auth::user();
        $privateItems = $meeting->agendaItems()->where('is_private', true)->get();
        $shouldHear = fn (User $user): bool => ! $user->is($creator)
            && $privateItems->every(fn (AgendaItem $item): bool => AgendaItemPresenter::canRead($item, $user));
        $overseers = $overseers->filter($shouldHear);
        $followers = $followers->filter($shouldHear);

        if ($overseers->isNotEmpty()) {
            Notification::send($overseers, new MeetingCreatedNotification($meeting));
        }

        if ($followers->isNotEmpty()) {
            Notification::send($followers, new MeetingCreatedNotification($meeting)->viaFollow());
        }
    }

    /**
     * Trashing a meeting deletes its tasks (HasTasks), so restoring it hands back the open one. A filled-in
     * agenda gets none: recreating its task only to close it would mail everyone a completion notice.
     */
    public function handleMeetingRestored(Meeting $meeting): void
    {
        $meeting->load(['institutions.tenant', 'agendaItems.votes']);

        if ($this->completionService->calculate($meeting) === 'complete') {
            return;
        }

        $this->createAgendaTask($meeting);
    }

    /**
     * Assigned to the nominated secretaries when the term has any, else the representatives who
     * were actually active at the meeting date. See ResolveTaskAssignees.
     */
    protected function createAgendaTask(Meeting $meeting): ?Task
    {
        $representatives = ResolveTaskAssignees::forMeeting($meeting);

        if ($representatives->isEmpty()) {
            return null;
        }

        if ($meeting->agendaItems->isEmpty()) {
            return $this->creationHandler->findOrCreate(
                name: __('Sukurti posėdžio darbotvarkės klausimus'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->addDays(3)->toDateString(),
            );
        }

        return $this->completionHandler->findOrCreate(
            name: __('Užpildyti darbotvarkės klausimų informaciją'),
            meeting: $meeting,
            users: $representatives,
            dueDate: $meeting->start_time->addDays(7)->toDateString(),
        );
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

        ['overseers' => $overseers, 'followers' => $followers] = ResolveMeetingNotificationAudience::split($meeting);

        // The task's own people already get TaskAutoCompleted, and the completer needs no news.
        $taskCarriers = ResolveTaskAssignees::forMeeting($meeting);
        $overseers = $overseers->reject(fn (User $user): bool => $user->is($completedBy) || $taskCarriers->contains('id', $user->id));

        if ($overseers->isNotEmpty()) {
            Notification::send($overseers, new MeetingAgendaCompletedNotification($meeting, $completedBy));
        }

        if ($followers->isNotEmpty()) {
            Notification::send($followers, new MeetingAgendaCompletedNotification($meeting, $completedBy)->viaFollow());
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
