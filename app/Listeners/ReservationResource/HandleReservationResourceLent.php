<?php

namespace App\Listeners\ReservationResource;

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

        $reservationResource->reservation->storeTask(__('Grąžinti išteklių').' '.$reservationResource->resource->name.' '.__('iki').' '.$reservationResource->end_time, $reservationResource->reservation->users, $reservationResource->end_time);
    }
}
