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

class ReservationTaskSubscriber
{
    public function __construct(
        protected PickupTaskHandler $pickupHandler,
        protected ReturnTaskHandler $returnHandler,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            StateChanged::class,
            [self::class, 'handleStateChanged']
        );
    }

    public function handleStateChanged(StateChanged $event): void
    {
        $model = $event->model;

        if (! $model instanceof ReservationResource) {
            return;
        }

        $reservation = $model->reservation;

        $finalState = $event->finalState !== null ? $event->finalState::class : self::class;
        $resourceName = $model->resource->name ?? '';

        match ($finalState) {
            Reserved::class => $this->handleReservedState($model, $reservation),
            Lent::class => $this->handleLentState($model, $reservation, $resourceName),
            Returned::class => $this->handleReturnedState($reservation, $resourceName),
            default => null,
        };
    }

    protected function handleReservedState(ReservationResource $resource, $reservation): void
    {
        $this->pickupHandler->findOrCreate(
            name: __('Atsiimti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $resource->start_time,
        );
    }

    protected function handleLentState(ReservationResource $resource, $reservation, string $resourceName): void
    {
        $this->pickupHandler->incrementProgressForModel($reservation, $resourceName);

        $this->returnHandler->findOrCreate(
            name: __('Grąžinti rezervacijos išteklius'),
            model: $reservation,
            users: $reservation->users,
            dueDate: $resource->end_time,
        );
    }

    protected function handleReturnedState($reservation, string $resourceName): void
    {
        $this->returnHandler->incrementProgressForModel($reservation, $resourceName);
    }
}
