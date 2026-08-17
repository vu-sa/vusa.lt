<?php

namespace App\Policies;

use App\Models\Pivots\ReservationResource;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * A reservation resource carries no permissions of its own — it is a row on someone's
 * reservation — so every ability delegates to the parent Reservation's policy. This mirrors
 * what ReservationResourceController already does by hand for update/destroy, and gives
 * Gate a policy to resolve when the pivot itself is passed (e.g. ApprovalController@history).
 *
 * Without a policy here, Gate denies every ability on this model, which fails closed but also
 * locks legitimate reservation owners and resource managers out.
 */
class ReservationResourcePolicy
{
    public function view(User $user, ReservationResource $reservationResource): bool
    {
        return $this->throughReservation($user, $reservationResource, 'view');
    }

    public function update(User $user, ReservationResource $reservationResource): bool
    {
        return $this->throughReservation($user, $reservationResource, 'update');
    }

    public function delete(User $user, ReservationResource $reservationResource): bool
    {
        return $this->throughReservation($user, $reservationResource, 'delete');
    }

    /**
     * Resolve the ability against the owning reservation, denying when it cannot be loaded.
     */
    private function throughReservation(User $user, ReservationResource $reservationResource, string $ability): bool
    {
        $reservation = $reservationResource->reservation;

        if ($reservation === null) {
            return false;
        }

        return Gate::forUser($user)->allows($ability, $reservation);
    }
}
