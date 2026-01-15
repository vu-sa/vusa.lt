<?php

namespace App\Listeners\Approval;

use App\Contracts\Approvable;
use App\Events\ApprovalRequested;
use App\Events\TaskCreated;
use App\Models\Task;
use App\Tasks\Enums\ActionType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Creates a task with action_type='approval' when approval is requested.
 * This task will be auto-completed when the approval decision is made.
 */
class CreateApprovalTask implements ShouldQueue
{
    public function handle(ApprovalRequested $event): void
    {
        $approvable = $event->approvable;
        $step = $event->step;

        // Ensure the model implements Approvable
        if (! $approvable instanceof Approvable) {
            return;
        }

        // Get approvers for this step
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

        // Create the task
        $task = new Task;
        $task->fill([
            'name' => __('Patvirtinti arba atmesti').': '.$displayName,
            'taskable_id' => $taskable->id,
            'taskable_type' => get_class($taskable),
            'action_type' => ActionType::Approval,
            'due_date' => $dueDate,
        ]);

        DB::transaction(function () use ($task, $approvers) {
            $task->save();
            $task->users()->sync($approvers->pluck('id'));
        });

        $task->refresh();

        event(new TaskCreated($task));
    }

    /**
     * Get the model that should own the task.
     * For pivot models like ReservationResource, return the parent.
     */
    protected function getTaskableModel($approvable)
    {
        // ReservationResource tasks should be attached to Reservation
        if (method_exists($approvable, 'reservation')) {
            return $approvable->reservation;
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
