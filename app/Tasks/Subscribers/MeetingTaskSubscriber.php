<?php

namespace App\Tasks\Subscribers;

use App\Actions\GetMeetingAdministrators;
use App\Events\MeetingFullyCreated;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

/**
 * Event subscriber for meeting-related task and notification operations.
 *
 * Consolidates task creation and progress tracking for meetings:
 * - Creates Agenda Creation tasks when meetings are created (no agenda items yet)
 * - Creates Agenda Completion tasks when agenda items exist but need filling
 * - Sends notifications to administrators on meeting creation and completion
 */
class MeetingTaskSubscriber
{
    public function __construct(
        protected AgendaCreationTaskHandler $creationHandler,
        protected AgendaCompletionTaskHandler $completionHandler,
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
                dueDate: $meeting->start_time?->addDays(3)->toDateString(),
            );
        }

        // If meeting was created WITH agenda items, create an "agenda completion" task
        if ($representatives->isNotEmpty() && $meeting->agendaItems->isNotEmpty()) {
            $this->completionHandler->findOrCreate(
                name: __('Užpildyti darbotvarkės klausimų informaciją'),
                meeting: $meeting,
                users: $representatives,
                dueDate: $meeting->start_time?->toDateString(),
            );
        }

        // Notify administrators about the new meeting
        $administrators = GetMeetingAdministrators::execute($meeting);

        if ($administrators->isNotEmpty()) {
            Notification::send($administrators, new MeetingCreatedNotification($meeting));
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

        // Update progress and check if completed
        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting);

        if ($wasCompleted) {
            $this->notifyAdministratorsOfCompletion($meeting);
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

        // Complete the "create agenda items" task if it exists
        $this->creationHandler->completeForMeeting($meeting);

        // Check if completion task exists
        $existingCompletionTask = $this->completionHandler->findExistingTask($meeting);

        if ($existingCompletionTask) {
            // Update total items count
            $this->completionHandler->updateProgressForMeeting($meeting);
        } else {
            // Create completion task for filling agenda item details
            $representatives = $meeting->getRepresentativesActiveAt();

            if ($representatives->isNotEmpty()) {
                $this->completionHandler->findOrCreate(
                    name: __('Užpildyti darbotvarkės klausimų informaciją'),
                    meeting: $meeting,
                    users: $representatives,
                    dueDate: $meeting->start_time?->addDays(7)->toDateString(),
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

        $wasCompleted = $this->completionHandler->updateProgressForMeeting($meeting);

        if ($wasCompleted) {
            $this->notifyAdministratorsOfCompletion($meeting);
        }
    }

    /**
     * Notify administrators when all agenda items are completed.
     */
    protected function notifyAdministratorsOfCompletion(Meeting $meeting): void
    {
        $meeting->load(['institutions.tenant']);

        $administrators = GetMeetingAdministrators::execute($meeting);

        if ($administrators->isNotEmpty()) {
            Notification::send($administrators, new MeetingAgendaCompletedNotification($meeting));
        }
    }
}
