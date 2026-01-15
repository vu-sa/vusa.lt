<?php

namespace App\Listeners\ReservationResource;

use App\Services\TaskService;
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
        $reservation = $reservationResource->reservation;

        // Create pickup task with action_type for potential auto-completion
        TaskService::storeTask(
            __('Atsiimti išteklių').' '.$reservationResource->resource->name,
            $reservation,
            $reservation->users,
            $reservationResource->start_time,
            'pickup'
        );
    }
}
