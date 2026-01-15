<?php

namespace App\Listeners\ReservationResource;

use App\Services\TaskService;
use App\States\ReservationResource\Lent;
use Spatie\ModelStates\Events\StateChanged;

class HandleReservationResourceLent
{
    public function handle(StateChanged $event)
    {
        if (get_class($event->finalState) !== Lent::class) {
            return;
        }

        $reservationResource = $event->model;
        $reservation = $reservationResource->reservation;

        // Create return task with action_type for potential auto-completion
        TaskService::storeTask(
            __('Grąžinti išteklių').' '.$reservationResource->resource->name.' '.__('iki').' '.$reservationResource->end_time,
            $reservation,
            $reservation->users,
            $reservationResource->end_time,
            'return'
        );
    }
}
