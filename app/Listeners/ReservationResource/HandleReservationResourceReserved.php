<?php

namespace App\Listeners\ReservationResource;

use App\Services\TaskService;
use App\States\ReservationResource\Reserved;
use App\Tasks\Enums\ActionType;
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

        // Find or create a pickup task with progress tracking
        // One task per reservation that tracks all resources
        TaskService::findOrCreateProgressTask(
            __('Atsiimti rezervacijos išteklius'),
            $reservation,
            $reservation->users,
            $reservationResource->start_time,
            ActionType::Pickup
        );
    }
}
