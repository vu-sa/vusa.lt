<?php

namespace App\Listeners\ReservationResource;

use App\Models\Pivots\ReservationResource;
use App\Models\Task;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Returned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Auto-completes tasks when reservation resource state changes:
 * - Completes 'pickup' tasks when state changes to Lent
 * - Completes 'return' tasks when state changes to Returned
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

        // Complete pickup tasks when state changes to Lent
        if ($finalState === Lent::class) {
            $this->completeTasksByActionType($reservation, 'pickup', $model->resource->name);
        }

        // Complete return tasks when state changes to Returned
        if ($finalState === Returned::class) {
            $this->completeTasksByActionType($reservation, 'return', $model->resource->name);
        }
    }

    /**
     * Complete tasks of a specific action type for a reservation.
     */
    protected function completeTasksByActionType($reservation, string $actionType, string $resourceName): void
    {
        Task::query()
            ->where('action_type', $actionType)
            ->whereNull('completed_at')
            ->where('taskable_type', get_class($reservation))
            ->where('taskable_id', $reservation->getKey())
            ->where('name', 'like', '%'.$resourceName.'%')
            ->update(['completed_at' => now()]);
    }
}
