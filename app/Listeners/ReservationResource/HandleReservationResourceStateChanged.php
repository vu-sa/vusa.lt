<?php

namespace App\Listeners\ReservationResource;

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChangedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Notifies a reservation's users when one of its resources changes state. Runs in the request, not
 * the queue, because spatie's StateChanged carries no actor and auth() is empty in a worker.
 */
class HandleReservationResourceStateChanged
{
    /**
     * Handle the event.
     */
    public function handle(StateChanged $event): void
    {
        $model = $event->model;

        // Only handle ReservationResource state changes
        if (! $model instanceof ReservationResource) {
            return;
        }

        // Get the state names
        $oldState = class_basename($event->initialState);
        $newState = class_basename($event->finalState);

        // Don't notify for Created state (initial state)
        if (strtolower($oldState) === 'created' && strtolower($newState) === 'created') {
            return;
        }

        // Load the reservation with users
        /** @var Reservation|null $reservation */
        $reservation = $model->reservation()->with('users')->first();

        if (! $reservation || $reservation->users->isEmpty()) {
            return;
        }

        $changedBy = auth()->user() instanceof User ? auth()->user() : null;

        $notification = new ReservationStatusChangedNotification(
            $model,
            strtolower($oldState),
            strtolower($newState),
            $changedBy
        );

        Notification::send($reservation->users->reject(fn (User $user): bool => $user->is($changedBy)), $notification);
    }
}
