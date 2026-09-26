<?php

namespace App\Tasks\Synchronizers;

use App\Models\Reservation;
use App\Models\Task;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use App\States\ReservationResource\Returned;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\PickupTaskHandler;
use App\Tasks\Handlers\ReturnTaskHandler;
use Illuminate\Support\Carbon;

class ReservationTaskSynchronizer
{
    public function __construct(
        private readonly PickupTaskHandler $pickupHandler,
        private readonly ReturnTaskHandler $returnHandler,
    ) {}

    public function sync(Reservation $reservation, bool $notifyCompletions): void
    {
        $reservation->load(['resources', 'users']);

        $pivots = $reservation->resources->pluck('pivot');

        $pickupPivots = $pivots->filter(fn ($pivot) => in_array($pivot->state->getValue(), [
            Reserved::$name,
            Lent::$name,
            Returned::$name,
        ], true));

        $returnPivots = $pivots->filter(fn ($pivot) => in_array($pivot->state->getValue(), [
            Lent::$name,
            Returned::$name,
        ], true));

        $this->syncTask(
            reservation: $reservation,
            actionType: ActionType::Pickup,
            total: $pickupPivots->count(),
            completed: $pickupPivots->filter(fn ($pivot) => in_array($pivot->state->getValue(), [Lent::$name, Returned::$name], true))->count(),
            dueDate: $pickupPivots->max('start_time'),
            notifyCompletion: $notifyCompletions,
        );

        $this->syncTask(
            reservation: $reservation,
            actionType: ActionType::Return,
            total: $returnPivots->count(),
            completed: $returnPivots->filter(fn ($pivot) => $pivot->state->getValue() === Returned::$name)->count(),
            dueDate: $returnPivots->max('end_time'),
            notifyCompletion: $notifyCompletions,
        );
    }

    private function syncTask(
        Reservation $reservation,
        ActionType $actionType,
        int $total,
        int $completed,
        ?Carbon $dueDate,
        bool $notifyCompletion,
    ): void {
        $task = $this->findTask($reservation, $actionType);

        if ($task === null && $total === 0) {
            return;
        }

        $task ??= match ($actionType) {
            ActionType::Pickup => $this->pickupHandler->findOrCreate(
                name: __('Atsiimti rezervacijos išteklius'),
                model: $reservation,
                users: $reservation->users,
                dueDate: $dueDate?->toString(),
            ),
            ActionType::Return => $this->returnHandler->findOrCreate(
                name: __('Grąžinti rezervacijos išteklius'),
                model: $reservation,
                users: $reservation->users,
                dueDate: $dueDate?->toString(),
            ),
            default => throw new \LogicException('Unsupported reservation task type.'),
        };

        $wasCompleted = $task->completed_at !== null;
        $isCompleted = $total === 0 || $completed >= $total;

        $task->forceFill([
            'metadata' => [
                'items_total' => $total,
                'items_completed' => $completed,
            ],
            'due_date' => $dueDate,
            'completed_at' => $isCompleted ? ($task->completed_at ?? now()) : null,
        ])->save();

        if (! $isCompleted) {
            $task->users()->sync($reservation->users->pluck('id'));
        }

        if (! $wasCompleted && $isCompleted && $total > 0 && $notifyCompletion) {
            match ($actionType) {
                ActionType::Pickup => $this->pickupHandler->complete($task, __('All items have been processed')),
                ActionType::Return => $this->returnHandler->complete($task, __('All items have been processed')),
                default => null,
            };
        }
    }

    private function findTask(Reservation $reservation, ActionType $actionType): ?Task
    {
        return Task::query()
            ->with('users')
            ->whereMorphedTo('taskable', $reservation)
            ->where('action_type', $actionType)
            ->orderByRaw('completed_at IS NULL DESC')
            ->latest('created_at')
            ->latest('id')
            ->first();
    }
}
