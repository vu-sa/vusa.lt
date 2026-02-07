<?php

namespace App\Tasks\Subscribers;

use App\Models\Pivots\ReservationResource;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use App\States\ReservationResource\Returned;
use App\Tasks\Handlers\PickupTaskHandler;
use App\Tasks\Handlers\ReturnTaskHandler;
use Illuminate\Events\Dispatcher;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Event subscriber for reservation-related task operations.
 *
 * Consolidates task creation and progress tracking for reservation resources:
 * - Creates Pickup tasks when resources are Reserved
 * - Creates Return tasks when resources are Lent
 * - Increments progress when resources change state
 */
class ReservationTaskSubscriber
{
    public function __construct(
        protected PickupTaskHandler $pickupHandler,
        protected ReturnTaskHandler $returnHandler,
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            StateChanged::class,
            [self::class, 'handleStateChanged']
        );
    }

    /**
     * Handle state changes for reservation resources.
     */
    public function handleStateChanged(StateChanged $event): void
    {
        $model = $event->model;

        // Only handle ReservationResource state changes
        if (! $model instanceof ReservationResource) {
            return;
        }

        $reservation = $model->reservation;

        $finalState = get_class($event->finalState);
        $resourceName = $model->resource->name ?? '';

        match ($finalState) {
            Reserved::class => $this->handleReservedState($model, $reservation),
            Lent::class => $this->handleLentState($model, $reservation, $resourceName),
            Returned::class => $this->handleReturnedState($reservation, $resourceName),
            default => null,
        };
    }

    /**
     * Handle transition to Reserved state - create pickup task.
     */
    protected function handleReservedState(ReservationResource $resource, $reservation): void
    {
        $this->pickupHandler->findOrCreate(
            name: __('Atsiimti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $resource->start_time,
        );
    }

    /**
     * Handle transition to Lent state:
     * - Increment pickup task progress
     * - Create return task
     */
    protected function handleLentState(ReservationResource $resource, $reservation, string $resourceName): void
    {
        // Increment pickup task progress
        $this->pickupHandler->incrementProgressForModel($reservation, $resourceName);

        // Create return task
        $this->returnHandler->findOrCreate(
            name: __('Grąžinti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $resource->end_time,
        );
    }

    /**
     * Handle transition to Returned state - increment return task progress.
     */
    protected function handleReturnedState($reservation, string $resourceName): void
    {
        $this->returnHandler->incrementProgressForModel($reservation, $resourceName);
    }
}
