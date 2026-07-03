<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ResourceAvailabilityRequest;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Provides date-range-aware capacity for resources.
 *
 * The Typesense resource index only carries static `capacity` / `is_reservable`,
 * so the search-powered reservation picker calls this endpoint to learn how much
 * of each resource is actually free within the chosen reservation window.
 */
class ResourceAvailabilityApiController extends ApiController
{
    /**
     * Return per-resource availability (and overlapping reservations) for a range.
     *
     * @route POST /api/v1/admin/resources/availability
     *
     * @routeName api.v1.admin.resources.availability
     */
    public function index(ResourceAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $start = (int) $validated['start'];
        $end = (int) $validated['end'];

        $rangeStart = Carbon::createFromTimestampMs($start);
        $rangeEnd = Carbon::createFromTimestampMs($end);

        $resources = Resource::query()
            ->whereIn('id', $validated['ids'])
            ->with(['active_reservations'])
            ->get();

        $data = $resources->map(function (Resource $resource) use ($start, $end, $rangeStart, $rangeEnd) {
            $capacityTimeline = $resource->getCapacityAtDateTimeRange($start, $end);

            $overlappingReservations = $resource->active_reservations
                ->filter(function ($reservation) use ($rangeStart, $rangeEnd) {
                    $pivotStart = $reservation->pivot->start_time;
                    $pivotEnd = $reservation->pivot->end_time;

                    if ($pivotStart === null || $pivotEnd === null) {
                        return false;
                    }

                    return $pivotStart->lt($rangeEnd) && $pivotEnd->gt($rangeStart);
                })
                ->map(fn ($reservation) => [
                    'id' => (string) $reservation->id,
                    'name' => $reservation->name,
                    'quantity' => (int) $reservation->pivot->quantity,
                    'state' => $reservation->pivot->state,
                    'start_time' => $reservation->pivot->start_time?->timestamp,
                    'end_time' => $reservation->pivot->end_time?->timestamp,
                ])
                ->values();

            return [
                'id' => (string) $resource->id,
                'capacity' => $resource->capacity,
                'is_reservable' => $resource->is_reservable,
                'lowestCapacityAtDateTimeRange' => $resource->lowestCapacityAtDateTimeRange($capacityTimeline),
                'reservations' => $overlappingReservations,
            ];
        })->keyBy('id');

        return $this->jsonSuccess($data);
    }
}
