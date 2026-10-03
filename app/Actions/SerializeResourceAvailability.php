<?php

namespace App\Actions;

use App\Models\Reservation;
use App\Models\Resource;
use Carbon\Carbon;

/**
 * The one availability payload for a resource over a period — the create form, the availability
 * API and the reservation cart all read it, so they cannot disagree about how much is free.
 *
 * `lowestCapacityAtDateTimeRange` is the flexible figure (active reservations whose time already
 * ended are ignored); it is what the UI caps quantities at and what checkout enforces.
 */
class SerializeResourceAvailability
{
    /**
     * @return array{capacity: int, is_reservable: bool, lowestCapacityAtDateTimeRange: int, strictLowestCapacityAtDateTimeRange: int, discrepancies: list<array<string, mixed>>}
     */
    public static function execute(Resource $resource, Carbon|int $start, Carbon|int $end): array
    {
        $strictTimeline = $resource->getCapacityAtDateTimeRange($start, $end);
        $flexibleTimeline = $resource->getCapacityAtDateTimeRange($start, $end, [], [], true);

        return [
            'capacity' => (int) $resource->capacity,
            'is_reservable' => (bool) $resource->is_reservable,
            'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($flexibleTimeline),
            'strictLowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($strictTimeline),
            'discrepancies' => $resource->findTimeEndedActiveReservations($start, $end)
                ->map(fn (Reservation $reservation) => self::reservationRow($reservation))
                ->values()
                ->all(),
        ];
    }

    /**
     * The flexible free quantity alone, for callers that only need the number.
     */
    public static function available(Resource $resource, Carbon|int $start, Carbon|int $end): int
    {
        return $resource->lowestCapacityAtDateTimeRange(
            $resource->getCapacityAtDateTimeRange($start, $end, [], [], true)
        );
    }

    /**
     * @return array{id: string, name: string, quantity: int, state: string, start_time: int|null, end_time: int|null}
     */
    public static function reservationRow(Reservation $reservation): array
    {
        return [
            'id' => (string) $reservation->id,
            'name' => $reservation->name,
            'quantity' => (int) $reservation->pivot->quantity,
            'state' => (string) $reservation->pivot->state,
            'start_time' => $reservation->pivot->start_time?->timestamp,
            'end_time' => $reservation->pivot->end_time?->timestamp,
        ];
    }
}
