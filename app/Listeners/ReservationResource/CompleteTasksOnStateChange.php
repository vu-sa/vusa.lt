<?php

namespace App\Listeners\ReservationResource;

use App\Models\Pivots\ReservationResource;
use App\Models\Task;
use App\Notifications\TaskAutoCompletedNotification;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Returned;
use App\Tasks\Enums\ActionType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Updates task progress when reservation resource state changes:
 * - Increments 'pickup' task progress when state changes to Lent
 * - Increments 'return' task progress when state changes to Returned
 *
 * Tasks auto-complete when all items are done.
 */
class CompleteTasksOnStateChange implements ShouldQueue
{
    public function handle(StateChanged $event): void
    {
        $model = $event->model;

        // Only handle ReservationResource state changes
        if (! $model instanceof ReservationResource) {
            return;
        }

        $reservation = $model->reservation;
        if (! $reservation) {
            return;
        }

        $finalState = get_class($event->finalState);
        $resourceName = $model->resource->name ?? '';

        // Increment pickup task progress when state changes to Lent
        if ($finalState === Lent::class) {
            $this->incrementTaskProgress(
                $reservation,
                ActionType::Pickup,
                __('Resource ":resource" was picked up', ['resource' => $resourceName])
            );
        }

        // Increment return task progress when state changes to Returned
        if ($finalState === Returned::class) {
            $this->incrementTaskProgress(
                $reservation,
                ActionType::Return,
                __('Resource ":resource" was returned', ['resource' => $resourceName])
            );
        }
    }

    /**
     * Increment progress on a task and notify users if task completes.
     */
    protected function incrementTaskProgress($reservation, ActionType $actionType, string $reason): void
    {
        $task = Task::query()
            ->with('users')
            ->where('action_type', $actionType)
            ->whereNull('completed_at')
            ->where('taskable_type', get_class($reservation))
            ->where('taskable_id', $reservation->getKey())
            ->first();

        if (! $task) {
            return;
        }

        // Increment progress - this will auto-complete if all items done
        $completed = $task->incrementProgress();

        // If task was completed, notify all assigned users
        if ($completed) {
            foreach ($task->users as $user) {
                $user->notify(new TaskAutoCompletedNotification(
                    $task,
                    __('All items have been processed')
                ));
            }
        }
    }
}
