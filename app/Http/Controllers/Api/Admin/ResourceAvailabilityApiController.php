<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SerializeResourceAvailability;
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
            $overlappingReservations = $resource->active_reservations
                ->filter(function ($reservation) use ($rangeStart, $rangeEnd) {
                    $pivotStart = $reservation->pivot->start_time;
                    $pivotEnd = $reservation->pivot->end_time;

                    if ($pivotStart === null || $pivotEnd === null) {
                        return false;
                    }

                    return $pivotStart->lt($rangeEnd) && $pivotEnd->gt($rangeStart);
                })
                ->map(fn ($reservation) => SerializeResourceAvailability::reservationRow($reservation))
                ->values()
                ->all();

            return [
                'id' => (string) $resource->id,
                ...SerializeResourceAvailability::execute($resource, $start, $end),
                'reservations' => $overlappingReservations,
            ];
        })->keyBy('id');

        return $this->jsonSuccess($data);
    }
}
