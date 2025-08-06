<?php

namespace App\Services;

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for calculating resource capacity and availability
 *
 * This service handles all complex capacity calculations, optimized for performance
 * and type safety while maintaining separation of concerns.
 */
class ResourceCapacityCalculator
{
    public function __construct(
        private Resource $resource
    ) {}

    /**
     * Calculate left capacity at a specific time
     *
     * @param  Carbon  $datetime  The time to check capacity at
     * @param  string  $symbolStart  Comparison operator for start time
     * @param  string  $symbolEnd  Comparison operator for end time
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude
     * @return int Available capacity at the specified time
     */
    public function calculateLeftCapacityAtTime(
        Carbon $datetime,
        string $symbolStart = '<',
        string $symbolEnd = '>=',
        array $exceptReservations = [],
        array $exceptResources = []
    ): int {
        $query = $this->buildActiveReservationsQuery($datetime, $symbolStart, $symbolEnd);

        $this->applyExceptions($query, $exceptReservations, $exceptResources);

        $usedCapacity = $query->sum('quantity');

        return $this->resource->capacity - $usedCapacity;
    }

    /**
     * Calculate capacity array (before and after) at a specific time
     *
     * @param  Carbon  $datetime  The time to check capacity at
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude
     * @return array{before: int, after: int} Capacity before and after the time
     */
    public function calculateCapacityAtTimeArray(
        Carbon $datetime,
        array $exceptReservations = [],
        array $exceptResources = []
    ): array {
        return [
            'before' => $this->calculateLeftCapacityAtTime($datetime, '<', '>=', $exceptReservations, $exceptResources),
            'after' => $this->calculateLeftCapacityAtTime($datetime, '<=', '>', $exceptReservations, $exceptResources),
        ];
    }

    /**
     * Get capacity timeline for a time range
     *
     * @param  TimeRange  $timeRange  The time range to analyze
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude
     * @return array<string, array{before: int, after: int, reservation?: array<string, mixed>, start?: bool, end?: bool}>
     */
    public function getCapacityTimeline(
        TimeRange $timeRange,
        array $exceptReservations = [],
        array $exceptResources = []
    ): array {
        $reservations = $this->getReservationsInRange($timeRange, $exceptReservations, $exceptResources);

        $capacityTimeline = [];

        // Add capacity points for each reservation start/end
        $this->addReservationCapacityPoints($capacityTimeline, $reservations, $timeRange);

        // Add capacity points for range boundaries
        $this->addRangeCapacityPoints($capacityTimeline, $timeRange, $exceptReservations, $exceptResources);

        // Sort by timestamp
        ksort($capacityTimeline);

        return $capacityTimeline;
    }

    /**
     * Find the lowest capacity within a given capacity timeline
     *
     * @param  array<string, array{after: int}>  $capacityTimeline
     * @return int The lowest available capacity
     */
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

    /**
     * Build query for active reservations within time constraints
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    private function buildActiveReservationsQuery(Carbon $datetime, string $symbolStart, string $symbolEnd)
    {
        return $this->resource
            ->active_reservations()
            ->wherePivot('start_time', $symbolStart, $datetime)
            ->wherePivot('end_time', $symbolEnd, $datetime);
    }

    /**
     * Apply exception filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $query
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     */
    private function applyExceptions($query, array $exceptReservations, array $exceptResources): void
    {
        if (! empty($exceptReservations) && in_array($this->resource->id, $exceptResources)) {
            $query->whereNotIn('reservations.id', $exceptReservations);
        }
    }

    /**
     * Get reservations within a time range
     *
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     * @return Collection<int, Reservation>
     */
    private function getReservationsInRange(
        TimeRange $timeRange,
        array $exceptReservations,
        array $exceptResources
    ): Collection {
        $query = $this->resource
            ->active_reservations()
            ->wherePivot('start_time', '<=', $timeRange->end)
            ->wherePivot('end_time', '>=', $timeRange->start);

        $this->applyExceptions($query, $exceptReservations, $exceptResources);

        /** @var Collection<int, Reservation> $result */
        $result = $query->get();

        return $result;
    }

    /**
     * Add capacity points for reservation starts and ends
     *
     * @param  array<int|string, mixed>  $capacityTimeline
     * @param  Collection<int, Reservation>  $reservations
     */
    private function addReservationCapacityPoints(array &$capacityTimeline, Collection $reservations, TimeRange $timeRange): void
    {
        $reservations->each(function (Reservation $reservation) use (&$capacityTimeline, $timeRange) {
            /** @var ReservationResource $pivot */
            $pivot = $reservation->pivot;

            $startTime = Carbon::parse($pivot->start_time);
            $endTime = Carbon::parse($pivot->end_time);

            // Clamp times to the requested range
            $effectiveStart = $startTime->isAfter($timeRange->start) ? $startTime : $timeRange->start;
            $effectiveEnd = $endTime->isBefore($timeRange->end) ? $endTime : $timeRange->end;

            $startKey = (string) $effectiveStart->getTimestampMs();
            $endKey = (string) $effectiveEnd->getTimestampMs();

            $capacityTimeline[$startKey] = $this->calculateCapacityAtTimeArray($effectiveStart) + [
                'reservation' => $this->formatReservationData($reservation),
                'start' => true,
            ];

            $capacityTimeline[$endKey] = $this->calculateCapacityAtTimeArray($effectiveEnd) + [
                'reservation' => $this->formatReservationData($reservation),
                'end' => true,
            ];
        });
    }

    /**
     * Add capacity points for time range boundaries
     *
     * @param  array<int|string, mixed>  $capacityTimeline
     * @param  array<int, string>  $exceptReservations
     * @param  array<int, string>  $exceptResources
     */
    private function addRangeCapacityPoints(
        array &$capacityTimeline,
        TimeRange $timeRange,
        array $exceptReservations,
        array $exceptResources
    ): void {
        $startKey = (string) $timeRange->getStartTimestampMs();
        $endKey = (string) $timeRange->getEndTimestampMs();

        $capacityTimeline[$startKey] = $this->calculateCapacityAtTimeArray($timeRange->start, $exceptReservations, $exceptResources);
        $capacityTimeline[$endKey] = $this->calculateCapacityAtTimeArray($timeRange->end, $exceptReservations, $exceptResources);
    }

    /**
     * Format reservation data for output (optimized to avoid full toArray())
     *
     * @return array<string, mixed>
     */
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
