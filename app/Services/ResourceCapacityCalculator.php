<?php

namespace App\Services;

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourceCapacityCalculator
{
    public function __construct(
        private readonly Resource $resource
    ) {}

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     */
    public function calculateLeftCapacityAtTime(
        Carbon $datetime,
        string $symbolStart = '<',
        string $symbolEnd = '>=',
        array $exceptReservations = [],
        array $exceptResources = [],
        bool $ignoreTimeEndedActive = false,
        ?int $excludedReservationResourceId = null
    ): int {
        $query = $this->buildActiveReservationsQuery($datetime, $symbolStart, $symbolEnd);

        $this->applyExceptions($query, $exceptReservations, $exceptResources, $excludedReservationResourceId);

        $usedCapacity = $query->sum('quantity');

        if ($ignoreTimeEndedActive) {
            $usedCapacity -= $this->calculateTimeEndedActiveCapacity($datetime, $symbolStart, $symbolEnd, $exceptReservations, $exceptResources, $excludedReservationResourceId);
        }

        return $this->resource->capacity - $usedCapacity;
    }

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     * @return array{before: int, after: int}
     */
    public function calculateCapacityAtTimeArray(
        Carbon $datetime,
        array $exceptReservations = [],
        array $exceptResources = [],
        bool $ignoreTimeEndedActive = false,
        ?int $excludedReservationResourceId = null
    ): array {
        return [
            'before' => $this->calculateLeftCapacityAtTime($datetime, '<', '>=', $exceptReservations, $exceptResources, $ignoreTimeEndedActive, $excludedReservationResourceId),
            'after' => $this->calculateLeftCapacityAtTime($datetime, '<=', '>', $exceptReservations, $exceptResources, $ignoreTimeEndedActive, $excludedReservationResourceId),
        ];
    }

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     * @return array<int, array{before: int, after: int, reservation?: array<string, mixed>, start?: bool, end?: bool}>
     */
    public function getCapacityTimeline(
        TimeRange $timeRange,
        array $exceptReservations = [],
        array $exceptResources = [],
        bool $ignoreTimeEndedActive = false,
        ?int $excludedReservationResourceId = null
    ): array {
        $reservations = $this->getReservationsInRange($timeRange, $exceptReservations, $exceptResources, $excludedReservationResourceId, includeTimelinePoints: true);

        return $this->buildTimeline($timeRange, $reservations, $ignoreTimeEndedActive ? Carbon::now()->toDateTimeString() : null);
    }

    /** @return array{strict: int, flexible: int, discrepancies: Collection<int, Reservation>} */
    public function summarizeAvailability(TimeRange $timeRange): array
    {
        $reservations = $this->getReservationsInRange($timeRange, includeTimelinePoints: true);
        $now = Carbon::now();

        return [
            'strict' => $this->findLowestCapacity($this->buildTimeline($timeRange, $reservations, null)),
            'flexible' => $this->findLowestCapacity($this->buildTimeline($timeRange, $reservations, $now->toDateTimeString())),
            'discrepancies' => $this->timeEndedActiveReservations($reservations, $now, $timeRange),
        ];
    }

    /** @return array{start: string, end: string} */
    public static function databaseWindowForInstants(TimeRange $timeRange): array
    {
        $start = self::localBoundary($timeRange->start, upper: false);
        $end = self::localBoundary($timeRange->end, upper: true);

        if ($end->microsecond !== 0) {
            $end->addSecond();
        }

        return ['start' => $start->toDateTimeString(), 'end' => $end->toDateTimeString()];
    }

    private static function localBoundary(Carbon $time, bool $upper): Carbon
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $offset = $timezone->getOffset($time);
        $timestamp = $time->timestamp;

        // Include both clock offsets near a daylight saving transition.
        foreach ($timezone->getTransitions($timestamp - 86400, $timestamp + 86400) ?: [] as $transition) {
            $offset = $upper ? max($offset, $transition['offset']) : min($offset, $transition['offset']);
        }

        return $time->copy()->utc()->addSeconds($offset);
    }

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     * @return Collection<int, Reservation>
     */
    public function findTimeEndedActiveReservations(
        TimeRange $timeRange,
        array $exceptReservations = [],
        array $exceptResources = []
    ): Collection {
        $now = Carbon::now();

        return $this->timeEndedActiveReservations(
            $this->getReservationsInRange($timeRange, $exceptReservations, $exceptResources),
            $now,
            $timeRange,
        );
    }

    /** @param array<array-key, array{after: int}> $capacityTimeline */
    public function findLowestCapacity(array $capacityTimeline): int
    {
        $lowestCapacity = $this->resource->capacity;

        foreach ($capacityTimeline as $capacityPoint) {
            if ($capacityPoint['after'] < $lowestCapacity) {
                $lowestCapacity = $capacityPoint['after'];
            }
        }

        return $lowestCapacity;
    }

    /** @return BelongsToMany<Reservation, Resource, ReservationResource, 'pivot'> */
    private function buildActiveReservationsQuery(Carbon $datetime, string $symbolStart, string $symbolEnd): BelongsToMany
    {
        return $this->resource
            ->active_reservations()
            ->wherePivot('start_time', $symbolStart, $datetime)
            ->wherePivot('end_time', $symbolEnd, $datetime);
    }

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     */
    private function calculateTimeEndedActiveCapacity(
        Carbon $datetime,
        string $symbolStart,
        string $symbolEnd,
        array $exceptReservations,
        array $exceptResources,
        ?int $excludedReservationResourceId
    ): int {
        $now = Carbon::now();

        $query = $this->resource
            ->active_reservations()
            ->wherePivot('start_time', $symbolStart, $datetime)
            ->wherePivot('end_time', $symbolEnd, $datetime)
            ->wherePivot('end_time', '<', $now);

        $this->applyExceptions($query, $exceptReservations, $exceptResources, $excludedReservationResourceId);

        return (int) $query->sum('quantity');
    }

    private function isTimeEndedActivePivot(ReservationResource $pivot, Carbon $now): bool
    {
        $state = (string) $pivot->state;

        if (! in_array($state, ['created', 'reserved', 'lent'], true)) {
            return false;
        }

        $endTime = $pivot->end_time;

        return $endTime !== null && $endTime->lt($now);
    }

    /**
     * @param  BelongsToMany<Reservation, Resource, ReservationResource, 'pivot'>  $query
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     */
    private function applyExceptions(BelongsToMany $query, array $exceptReservations, array $exceptResources, ?int $excludedReservationResourceId = null): void
    {
        if (! empty($exceptReservations) && in_array($this->resource->id, $exceptResources)) {
            $query->whereNotIn('reservations.id', $exceptReservations);
        }

        if ($excludedReservationResourceId !== null) {
            $query->wherePivot('id', '!=', $excludedReservationResourceId);
        }
    }

    /**
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     * @return Collection<int, Reservation>
     */
    private function getReservationsInRange(
        TimeRange $timeRange,
        array $exceptReservations = [],
        array $exceptResources = [],
        ?int $excludedReservationResourceId = null,
        bool $includeTimelinePoints = false
    ): Collection {
        $start = $timeRange->start->toDateTimeString();
        $end = $timeRange->end->toDateTimeString();

        if ($includeTimelinePoints) {
            // Interior points use the pivot timezone while range endpoints keep their input timezone.
            $localWindow = self::databaseWindowForInstants($timeRange);
            [$start, $end] = [min($start, $end, $localWindow['start']), max($start, $end, $localWindow['end'])];
        }

        $query = $this->resource
            ->active_reservations()
            ->wherePivot('start_time', '<=', $end)
            ->wherePivot('end_time', '>=', $start);

        $this->applyExceptions($query, $exceptReservations, $exceptResources, $excludedReservationResourceId);

        return $query->get();
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return Collection<int, Reservation>
     */
    private function timeEndedActiveReservations(Collection $reservations, Carbon $now, TimeRange $timeRange): Collection
    {
        $start = $timeRange->start->toDateTimeString();
        $end = $timeRange->end->toDateTimeString();

        return $reservations->filter(function (Reservation $reservation) use ($now, $start, $end): bool {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;

            return $this->pivotOverlapsWindow($pivot, $start, $end) && $this->isTimeEndedActivePivot($pivot, $now);
        })->values();
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return array<int, array{before: int, after: int, reservation?: array<string, mixed>, start?: bool, end?: bool}>
     */
    private function buildTimeline(TimeRange $timeRange, Collection $reservations, ?string $ignoreEndsBefore): array
    {
        $rangeStart = $timeRange->start->toDateTimeString();
        $rangeEnd = $timeRange->end->toDateTimeString();
        $pointTimes = [];
        $details = [];
        $quantityChanges = [];

        foreach ($reservations as $reservation) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;

            // Compare stored clock times at the same precision as the SQL bindings.
            $start = (string) $pivot->getRawOriginal('start_time');
            $end = (string) $pivot->getRawOriginal('end_time');

            if ($this->pivotOverlapsWindow($pivot, $rangeStart, $rangeEnd)) {
                $startTime = Carbon::parse($pivot->start_time);
                $endTime = Carbon::parse($pivot->end_time);
                $effectiveStart = $startTime->isAfter($timeRange->start) ? $startTime : $timeRange->start;
                $effectiveEnd = $endTime->isBefore($timeRange->end) ? $endTime : $timeRange->end;
                $payload = $this->formatReservationData($reservation);

                foreach (['start' => $effectiveStart, 'end' => $effectiveEnd] as $flag => $time) {
                    $key = $time->getTimestampMs();
                    $pointTimes[$key] = $time->toDateTimeString();
                    $details[$key] = ['reservation' => $payload, $flag => true];
                }
            }

            if ($start < $end && ($ignoreEndsBefore === null || $end >= $ignoreEndsBefore)) {
                $quantityChanges[$start] = ($quantityChanges[$start] ?? 0) + $pivot->quantity;
                $quantityChanges[$end] = ($quantityChanges[$end] ?? 0) - $pivot->quantity;
            }
        }

        foreach ([$timeRange->start, $timeRange->end] as $time) {
            $key = $time->getTimestampMs();
            $pointTimes[$key] = $time->toDateTimeString();
            $details[$key] = [];
        }

        $capacityByTime = $this->capacitiesAtPoints($quantityChanges + array_fill_keys($pointTimes, 0));
        $timeline = [];

        foreach ($pointTimes as $key => $time) {
            $timeline[$key] = $capacityByTime[$time] + $details[$key];
        }

        ksort($timeline);

        return $timeline;
    }

    private function pivotOverlapsWindow(ReservationResource $pivot, string $start, string $end): bool
    {
        return $pivot->getRawOriginal('start_time') <= $end && $pivot->getRawOriginal('end_time') >= $start;
    }

    /**
     * @param  array<string, int>  $quantityChanges
     * @return array<string, array{before: int, after: int}>
     */
    private function capacitiesAtPoints(array $quantityChanges): array
    {
        ksort($quantityChanges, SORT_STRING);

        $available = $this->resource->capacity;
        $points = [];

        foreach ($quantityChanges as $time => $quantityChange) {
            $after = $available - $quantityChange;
            $points[$time] = ['before' => $available, 'after' => $after];
            $available = $after;
        }

        return $points;
    }

    /** @return array{id: string, name: string, description: string|null, start_time: Carbon, end_time: Carbon} */
    private function formatReservationData(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'name' => $reservation->name,
            'description' => $reservation->description,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
        ];
    }
}
