<?php

namespace App\Listeners\ReservationResource;

use App\Events\ApprovalRequested;
use App\Events\ReservationResourceCreated;
use App\Models\Pivots\ReservationResource;

class HandleReservationResourceCreated
{
    public function handle(ReservationResourceCreated $event)
    {
        $reservationResource = $event->reservationResource;

        // check if reservationResource is instance of ReservationResource
        if (! $reservationResource instanceof ReservationResource) {
            return;
        }

        // Dispatch approval requested event which will create task and notify approvers
        event(new ApprovalRequested($reservationResource, 1));
    }
}
