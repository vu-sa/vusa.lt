<?php

namespace App\Tasks\Subscribers;

use App\Contracts\Approvable;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalRequested;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Handlers\ApprovalTaskHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;

/**
 * Event subscriber for approval-related task operations.
 *
 * Consolidates task creation and completion for approval workflow:
 * - Creates Approval tasks when approval is requested
 * - Completes Approval tasks when a decision is made
 */
class ApprovalTaskSubscriber
{
    public function __construct(
        protected ApprovalTaskHandler $approvalHandler,
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ApprovalRequested::class,
            [self::class, 'handleApprovalRequested']
        );

        $events->listen(
            ApprovalDecisionMade::class,
            [self::class, 'handleApprovalDecisionMade']
        );
    }

    /**
     * Create an approval task when approval is requested.
     */
    public function handleApprovalRequested(ApprovalRequested $event): void
    {
        $approvable = $event->approvable;
        $step = $event->step;

        // Get approvers for this step
        /** @var Approvable&Model $approvable */
        $approvers = $approvable->getApproversForStep($step);

        if ($approvers->isEmpty()) {
            return;
        }

        // Determine the taskable model (for ReservationResource, it's the Reservation)
        $taskable = $this->getTaskableModel($approvable);

        // Get the display name for the task
        $displayName = $approvable->getApprovalDisplayName();

        // Get due date from flow configuration if available
        $dueDate = $this->getDueDate($approvable, $step);

        // Create the approval task
        $data = CreateTaskData::approval(
            name: __('Patvirtinti arba atmesti').': '.$displayName,
            taskable: $taskable,
            users: $approvers,
            dueDate: $dueDate,
        );

        $this->approvalHandler->create($data);
    }

    /**
     * Complete approval tasks when a decision is made.
     */
    public function handleApprovalDecisionMade(ApprovalDecisionMade $event): void
    {
        $approvable = $event->approvable;
        $reason = __('Approval decision was made');

        // Complete tasks for the approvable itself
        $this->approvalHandler->completeForModel($approvable, $reason);

        // For ReservationResource, also check tasks on the Reservation
        if (method_exists($approvable, 'reservation')) {
            $reservation = $approvable->reservation()->first();

            if ($reservation) {
                $this->approvalHandler->completeForModel($reservation, $reason);
            }
        }
    }

    /**
     * Get the model that should own the task.
     * For pivot models like ReservationResource, return the parent.
     *
     * @param  Model&Approvable  $approvable
     */
    protected function getTaskableModel($approvable)
    {
        // ReservationResource tasks should be attached to Reservation
        if (method_exists($approvable, 'reservation')) {
            return $approvable->reservation()->first();
        }

        return $approvable;
    }

    /**
     * Calculate due date from flow configuration.
     */
    protected function getDueDate($approvable, int $step): ?string
    {
        $flow = $approvable->getApprovalFlow();

        if (! $flow) {
            return null;
        }

        $deadlineDays = $flow->getDeadlineDaysForStep($step);

        if (! $deadlineDays) {
            return null;
        }

        return now()->addDays($deadlineDays)->toDateString();
    }
}
