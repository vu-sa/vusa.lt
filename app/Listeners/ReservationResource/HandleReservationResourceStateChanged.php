<?php

namespace App\Listeners\ReservationResource;

use App\Models\Pivots\ReservationResource;
use App\Notifications\ReservationStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Listener to notify users when a reservation resource state changes.
 */
class HandleReservationResourceStateChanged implements ShouldQueue
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
        $reservation = $model->reservation()->with('users')->first();

        if (! $reservation || $reservation->users->isEmpty()) {
            return;
        }

        // Get the current user who made the change
        $changedBy = auth()->user();

        $notification = new ReservationStatusChangedNotification(
            $model,
            strtolower($oldState),
            strtolower($newState),
            $changedBy
        );

        Notification::send($reservation->users, $notification);
    }
}
