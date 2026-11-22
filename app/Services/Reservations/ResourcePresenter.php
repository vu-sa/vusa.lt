<?php

namespace App\Services\Reservations;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\ResourceCapacityCalculator;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;

final class ResourcePresenter
{
    public const array RESERVATION_POLICY_RELATIONS = ['users', 'resources.tenant', ...Reservation::TENANT_RELATIONS];

    /**
     * The flexible figure ignores active reservations whose time has ended, matching checkout.
     *
     * @return array{capacity: int, is_reservable: bool, lowestCapacityAtDateTimeRange: int, strictLowestCapacityAtDateTimeRange: int, discrepancies: list<array{key: int, id: string, name: string, quantity: int, state: string, start_time: int|null, end_time: int|null}|array{key: int, id: null, name: null, quantity: int, state: string, start_time: int|null, end_time: int|null}>}
     */
    public static function availability(Resource $resource, Carbon|int $start, Carbon|int $end, User $viewer): array
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
     * Availability is public to every admin user, the reservations behind it are not: someone
     * else's reservation shows only its period and quantity.
     *
     * @return array{key: int, id: string, name: string, quantity: int, state: string, start_time: int|null, end_time: int|null}|array{key: int, id: null, name: null, quantity: int, state: string, start_time: int|null, end_time: int|null}
     */
    public static function reservationRow(Reservation $reservation, User $viewer): array
    {
        $pivot = $reservation->pivot;

        if (! $viewer->can('view', $reservation)) {
            return [
                'key' => (int) $pivot->id,
                'id' => null,
                'name' => null,
                'quantity' => (int) $pivot->quantity,
                'state' => (string) $pivot->state,
                'start_time' => $pivot->start_time?->timestamp,
                'end_time' => $pivot->end_time?->timestamp,
            ];
        }

        return [
            'key' => (int) $pivot->id,
            'id' => (string) $reservation->id,
            'name' => (string) $reservation->name,
            'quantity' => (int) $pivot->quantity,
            'state' => (string) $pivot->state,
            'start_time' => $pivot->start_time?->timestamp,
            'end_time' => $pivot->end_time?->timestamp,
        ];
    }

    /** @return list<array{id: string, name: string, email: string|null, phone: string|null, facebook_url: string|null, profile_photo_path: string|null}> */
    public static function managers(Resource $resource): array
    {
        return $resource->managers()->map(fn (User $user): array => [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'facebook_url' => $user->facebook_url,
            'profile_photo_path' => $user->profile_photo_path,
        ])->values()->all();
    }
}
