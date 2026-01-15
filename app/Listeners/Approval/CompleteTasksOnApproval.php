<?php

namespace App\Listeners\Approval;

use App\Events\ApprovalDecisionMade;
use App\Models\Task;
use App\Notifications\TaskAutoCompletedNotification;
use App\Tasks\Enums\ActionType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

/**
 * Automatically completes tasks with action_type='approval' when an approval decision is made.
 */
class CompleteTasksOnApproval implements ShouldQueue
{
    public function handle(ApprovalDecisionMade $event): void
    {
        $approvable = $event->approvable;

        // Get the morph class name for the approvable
        $morphClass = $approvable->getMorphClass();

        // Also check for snake_case version (e.g., 'reservation_resource')
        $snakeCaseClass = Str::snake(class_basename($approvable));

        // Find tasks linked to this approvable with action_type='approval' and complete them
        $tasks = Task::query()
            ->with('users')
            ->where('action_type', ActionType::Approval)
            ->whereNull('completed_at')
            ->where(function ($query) use ($approvable, $morphClass, $snakeCaseClass) {
                $query->where(function ($q) use ($approvable, $morphClass) {
                    $q->where('taskable_type', $morphClass)
                        ->where('taskable_id', $approvable->getKey());
                })->orWhere(function ($q) use ($approvable, $snakeCaseClass) {
                    $q->where('taskable_type', $snakeCaseClass)
                        ->where('taskable_id', $approvable->getKey());
                });
            })
            ->get();

        $this->completeTasks($tasks, __('Approval decision was made'));

        // For ReservationResource, also check tasks on the Reservation
        if (method_exists($approvable, 'reservation')) {
            $reservation = $approvable->reservation;

            if ($reservation) {
                $reservationMorphClass = $reservation->getMorphClass();
                $reservationSnakeCase = Str::snake(class_basename($reservation));

                $reservationTasks = Task::query()
                    ->with('users')
                    ->where('action_type', ActionType::Approval)
                    ->whereNull('completed_at')
                    ->where(function ($query) use ($reservation, $reservationMorphClass, $reservationSnakeCase) {
                        $query->where(function ($q) use ($reservation, $reservationMorphClass) {
                            $q->where('taskable_type', $reservationMorphClass)
                                ->where('taskable_id', $reservation->getKey());
                        })->orWhere(function ($q) use ($reservation, $reservationSnakeCase) {
                            $q->where('taskable_type', $reservationSnakeCase)
                                ->where('taskable_id', $reservation->getKey());
                        });
                    })
                    ->get();

                $this->completeTasks($reservationTasks, __('Approval decision was made'));
            }
        }
    }

    /**
     * Complete tasks and notify assigned users.
     */
    protected function completeTasks($tasks, string $reason): void
    {
        foreach ($tasks as $task) {
            $task->completed_at = now();
            $task->save();

            // Notify all assigned users
            foreach ($task->users as $user) {
                $user->notify(new TaskAutoCompletedNotification($task, $reason));
            }
        }
    }
}
