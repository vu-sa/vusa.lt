<?php

namespace App\Listeners\ReservationResource;

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChangeBatch;
use Spatie\ModelStates\Events\StateChanged;

use function Illuminate\Support\defer;

/**
 * Notifies a reservation's users when one of its resources changes state. Runs in the request, not
 * the queue, because spatie's StateChanged carries no actor and auth() is empty in a worker.
 */
class HandleReservationResourceStateChanged
{
    public function handle(StateChanged $event): void
    {
        $model = $event->model;

        if (! $model instanceof ReservationResource) {
            return;
        }

        $oldState = class_basename($event->initialState);
        $newState = class_basename($event->finalState);

        if (strtolower($oldState) === 'created' && strtolower($newState) === 'created') {
            return;
        }

        /** @var Reservation|null $reservation */
        $reservation = $model->reservation()->with('users')->first();

        if (! $reservation || $reservation->users->isEmpty()) {
            return;
        }

        $changedBy = auth()->user() instanceof User ? auth()->user() : null;

        $batch = app(ReservationStatusChangeBatch::class);
        $batch->add($model, strtolower($oldState), strtolower($newState), $changedBy);
        defer(fn () => $batch->flush(), 'reservation-status-changes');
    }
}
