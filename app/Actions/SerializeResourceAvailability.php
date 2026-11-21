<?php

namespace App\Actions;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\ResourceCapacityCalculator;
use App\ValueObjects\TimeRange;
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
    public const array RESERVATION_POLICY_RELATIONS = ['users', 'resources.tenant', ...Reservation::TENANT_RELATIONS];

    /**
     * @return array{capacity: int, is_reservable: bool, lowestCapacityAtDateTimeRange: int, strictLowestCapacityAtDateTimeRange: int, discrepancies: list<array<string, mixed>>}
     */
    public static function execute(Resource $resource, Carbon|int $start, Carbon|int $end, User $viewer): array
    {
        $calculator = new ResourceCapacityCalculator($resource);
        $availability = $calculator->summarizeAvailability(new TimeRange($start, $end));

        return [
            'capacity' => (int) $resource->capacity,
            'is_reservable' => (bool) $resource->is_reservable,
            'lowestCapacityAtDateTimeRange' => $availability['flexible'],
            'strictLowestCapacityAtDateTimeRange' => $availability['strict'],
            'discrepancies' => $availability['discrepancies']
                ->loadMissing(self::RESERVATION_POLICY_RELATIONS)
                ->map(fn (Reservation $reservation): array => self::reservationRow($reservation, $viewer))
                ->all(),
        ];
    }

    /**
     * The flexible free quantity alone, for callers that only need the number.
     */
    public static function available(Resource $resource, Carbon|int $start, Carbon|int $end, ?int $excludedReservationResourceId = null): int
    {
        return $resource->lowestCapacityAtDateTimeRange(
            $resource->getCapacityAtDateTimeRange($start, $end, [], [], true, $excludedReservationResourceId)
        );
    }

    /**
     * Availability is public to every admin user, the reservations behind it are not: someone
     * else's reservation shows only its period and quantity.
     *
     * @return array{key: int, id: string|null, name: string|null, quantity: int, state: string, start_time: int|null, end_time: int|null}
     */
    public static function reservationRow(Reservation $reservation, User $viewer): array
    {
        $visible = $viewer->can('view', $reservation);

        return [
            'key' => (int) $reservation->pivot->id,
            'id' => $visible ? (string) $reservation->id : null,
            'name' => $visible ? $reservation->name : null,
            'quantity' => (int) $reservation->pivot->quantity,
            'state' => (string) $reservation->pivot->state,
            'start_time' => $reservation->pivot->start_time?->timestamp,
            'end_time' => $reservation->pivot->end_time?->timestamp,
        ];
    }
}
