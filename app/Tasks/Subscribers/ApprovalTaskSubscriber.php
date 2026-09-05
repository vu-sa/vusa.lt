<?php

namespace App\Tasks\Subscribers;

use App\Contracts\Approvable;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalRequested;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Handlers\ApprovalTaskHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;

class ApprovalTaskSubscriber
{
    public function __construct(
        protected ApprovalTaskHandler $approvalHandler,
    ) {}

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

    public function handleApprovalRequested(ApprovalRequested $event): void
    {
        $approvable = $event->approvable;
        $step = $event->step;

        /** @var Approvable&Model $approvable */
        $approvers = $approvable->getApproversForStep($step);

        if ($approvers->isEmpty()) {
            return;
        }

        $taskable = $this->getTaskableModel($approvable);
        $displayName = $approvable->getApprovalDisplayName();
        $dueDate = $this->getDueDate($approvable, $step);

        $data = CreateTaskData::approval(
            name: __('Patvirtinti arba atmesti').': '.$displayName,
            taskable: $taskable,
            users: $approvers,
            dueDate: $dueDate,
        );

        $this->approvalHandler->create($data);
    }

    public function handleApprovalDecisionMade(ApprovalDecisionMade $event): void
    {
        $approvable = $event->approvable;
        $reason = __('Approval decision was made');

        $this->approvalHandler->completeForModel($approvable, $reason);

        // ReservationResource tasks are attached to the parent Reservation.
        if (method_exists($approvable, 'reservation')) {
            $reservation = $approvable->reservation()->first();

            if ($reservation) {
                $this->approvalHandler->completeForModel($reservation, $reason);
            }
        }
    }

    /**
     * @param  Model&Approvable  $approvable
     */
    protected function getTaskableModel($approvable)
    {
        if (method_exists($approvable, 'reservation')) {
            return $approvable->reservation()->first();
        }

        return $approvable;
    }

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
