<?php

namespace App\Tasks\Subscribers;

use App\Models\Pivots\ReservationResource;
use App\Tasks\Synchronizers\ReservationTaskSynchronizer;
use Illuminate\Events\Dispatcher;
use Spatie\ModelStates\Events\StateChanged;

class ReservationTaskSubscriber
{
    public function __construct(
        protected ReservationTaskSynchronizer $taskSynchronizer,
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

        $initialState = $event->initialState?->getValue();
        $finalState = $event->finalState?->getValue();
        $stateOrder = ['created', 'reserved', 'lent', 'returned'];
        $initialIndex = array_search($initialState, $stateOrder, true);
        $finalIndex = array_search($finalState, $stateOrder, true);
        $isForward = $initialIndex !== false && $finalIndex !== false && $initialIndex < $finalIndex;

        $this->taskSynchronizer->sync($model->reservation, $isForward);
    }
}
