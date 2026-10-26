<?php

namespace App\Actions;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;

class LoadReservationResourceApprovals
{
    /**
     * @param  iterable<Reservation>  $reservations
     * @param  list<string>  $relations
     */
    public static function execute(iterable $reservations, array $relations): void
    {
        $pivots = collect($reservations)
            ->flatMap(fn (Reservation $reservation) => $reservation->resources->pluck('pivot'));

        new Collection($pivots->all())->loadMissing($relations);
    }
}
