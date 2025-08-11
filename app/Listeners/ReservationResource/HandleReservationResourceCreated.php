<?php

namespace App\Listeners\ReservationResource;

use App\Events\ReservationResourceCreated;
use App\Models\Pivots\ReservationResource;
use App\Services\TaskService;

class HandleReservationResourceCreated
{
    public function handle(ReservationResourceCreated $event)
    {
        $reservationResource = $event->reservationResource;

        // check if reservationResource is instance of ReservationResource
        if (! $reservationResource instanceof ReservationResource) {
            return;
        }

        $resourceManagers = $reservationResource->resource->managers();

        TaskService::storeTask('Patvirtinti arba atšaukti rezervaciją', $reservationResource->reservation, $resourceManagers);
    }
}
