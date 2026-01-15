<?php

namespace App\Listeners\ReservationResource;

use App\Services\TaskService;
use App\States\ReservationResource\Lent;
use App\Tasks\Enums\ActionType;
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

        // Find or create a return task with progress tracking
        // One task per reservation that tracks all lent resources
        TaskService::findOrCreateProgressTask(
            __('Grąžinti rezervacijos išteklius'),
            $reservation,
            $reservation->users,
            $reservationResource->end_time,
            ActionType::Return
        );
    }
}
