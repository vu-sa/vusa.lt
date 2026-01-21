<?php

namespace App\Tasks\Subscribers;

use App\Actions\GetInstitutionFollowersToNotify;
use App\Actions\GetMeetingAdministrators;
use App\Events\MeetingFullyCreated;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Event subscriber for meeting-related task and notification operations.
 *
 * Consolidates task creation and progress tracking for meetings:
 * - Creates Agenda Creation tasks when meetings are created (no agenda items yet)
 * - Creates Agenda Completion tasks when agenda items exist but need filling
 * - Completes Periodicity Gap tasks when meetings are created for institutions
 * - Sends notifications to administrators on meeting creation and completion
 */
class MeetingTaskSubscriber
{
    public function __construct(
        protected AgendaCreationTaskHandler $creationHandler,
        protected AgendaCompletionTaskHandler $completionHandler,
        protected PeriodicityGapTaskHandler $periodicityGapHandler,
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        // Listen to custom event that fires after meeting is fully set up with relationships
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

    /**
     * Handle meeting fully created event.
     * Creates an agenda creation task and notifies administrators.
     * Also completes any pending periodicity gap tasks for the meeting's institutions.
     */
    public function handleMeetingCreated(MeetingFullyCreated $event): void
    {
        $meeting = $event->meeting;

        // Load required relationships
        $meeting->load(['institutions.tenant', 'agendaItems']);

        // Get student representatives active at the meeting time for task assignment
        $representatives = $meeting->getRepresentativesActiveAt();

        // If meeting was created WITHOUT agenda items, create an "agenda creation" task
        if ($representatives->isNotEmpty() && $meeting->agendaItems->isEmpty()) {
            $this->creationHandler->findOrCreate(
                name: __('Sukurti posėdžio darbotvarkės klausimus'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->addDays(3)->toDateString(),
            );
        }

        // If meeting was created WITH agenda items, create an "agenda completion" task
        if ($representatives->isNotEmpty() && $meeting->agendaItems->isNotEmpty()) {
            $this->completionHandler->findOrCreate(
                name: __('Užpildyti darbotvarkės klausimų informaciją'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time->toDateString(),
            );
        }

        // Complete any pending periodicity gap tasks for this meeting's institutions
        foreach ($meeting->institutions as $institution) {
            $this->periodicityGapHandler->completeForInstitution(
                institution: $institution,
                reason: __('tasks.periodicity_gap.completed_meeting_created'),
            );
        }

        // Notify administrators about the new meeting
        $administrators = GetMeetingAdministrators::execute($meeting);

        // Also notify followers of the meeting's institutions (excluding muted users)
        $followers = GetInstitutionFollowersToNotify::execute($meeting);

        // Merge and deduplicate recipients
        $recipients = $administrators->merge($followers)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MeetingCreatedNotification($meeting));
        }
    }

    /**
     * Handle agenda item saved (updated) event.
     * Updates task progress and checks for completion.
     */
    public function handleAgendaItemSaved(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        if (! $meeting) {
            return;
        }

        // Reload meeting with fresh agenda items data
        $meeting->load('agendaItems');

        // Get the user who made the change
        /** @var User|null $actor */
        $actor = Auth::user();

        // Update progress and check if completed
        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting, $actor);

        if ($wasCompleted) {
            $this->notifyAdministratorsOfCompletion($meeting, $actor);
        }
    }

    /**
     * Handle agenda item created event.
     * Completes creation task and creates/updates completion task.
     */
    public function handleAgendaItemCreated(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        if (! $meeting) {
            return;
        }

        $meeting->load('agendaItems');

        // Get the user who made the change
        /** @var User|null $actor */
        $actor = Auth::user();

        // Complete the "create agenda items" task if it exists
        $this->creationHandler->completeForMeeting($meeting, $actor);

        // Check if completion task exists
        $existingCompletionTask = $this->completionHandler->findExistingTask($meeting);

        if ($existingCompletionTask) {
            // Update total items count
            $this->completionHandler->updateProgressForMeeting($meeting, $actor);
        } else {
            // Create completion task for filling agenda item details
            $representatives = $meeting->getRepresentativesActiveAt();

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

    /**
     * Handle agenda item deleted event.
     * Updates the task total items count and may trigger completion.
     */
    public function handleAgendaItemDeleted(AgendaItem $agendaItem): void
    {
        $meeting = $agendaItem->meeting;

        if (! $meeting) {
            return;
        }

        $meeting->load('agendaItems');

        // Get the user who made the change
        /** @var User|null $actor */
        $actor = Auth::user();

        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting, $actor);

        if ($wasCompleted) {
            $this->notifyAdministratorsOfCompletion($meeting, $actor);
        }
    }

    /**
     * Notify administrators when all agenda items are completed.
     * The completedBy user is included to show who triggered the completion.
     *
     * @param  User|null  $completedBy  The user who completed the agenda (shown in notification)
     */
    protected function notifyAdministratorsOfCompletion(Meeting $meeting, ?User $completedBy = null): void
    {
        $meeting->load(['institutions.tenant']);

        $administrators = GetMeetingAdministrators::execute($meeting);

        // Also notify followers of the meeting's institutions (excluding muted users)
        $followers = GetInstitutionFollowersToNotify::execute($meeting);

        // Merge and deduplicate recipients
        $recipients = $administrators->merge($followers)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MeetingAgendaCompletedNotification($meeting, $completedBy));
        }
    }
}
