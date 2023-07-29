<?php

namespace App\Listeners\ReservationResource;

use App\States\ReservationResource\Reserved;
use Spatie\ModelStates\Events\StateChanged;

class HandleReservationResourceReserved
{
    public function handle(StateChanged $event)
    {
        if (get_class($event->finalState) !== Reserved::class) {
            return;
        }

        $reservationResource = $event->model;

        $reservationResource->reservation->storeTask(__('Atsiimti išteklių').' '.$reservationResource->resource->name, $reservationResource->reservation->users, $reservationResource->start_time);
    }
}
