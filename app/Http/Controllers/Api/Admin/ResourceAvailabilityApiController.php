<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ResourceAvailabilityRequest;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\Reservations\ResourcePresenter;
use App\Services\ResourceCapacityCalculator;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

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
    // Availability is only meaningful within the reservation-creation flow, so gate on the same ability the create form uses.
    #[Authorize('create', Reservation::class)]
    public function index(ResourceAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $start = (int) $validated['start'];
        $end = (int) $validated['end'];

        $rangeStart = Carbon::createFromTimestampMs($start);
        $rangeEnd = Carbon::createFromTimestampMs($end);
        $window = ResourceCapacityCalculator::databaseWindowForInstants(new TimeRange($rangeStart, $rangeEnd));

        $resources = Resource::query()
            ->whereIn('id', $validated['ids'])
            ->with([
                'active_reservations' => fn (BelongsToMany $query): BelongsToMany => $query
                    ->wherePivot('start_time', '<', $window['end'])
                    ->wherePivot('end_time', '>', $window['start'])
                    ->with(ResourcePresenter::RESERVATION_POLICY_RELATIONS),
            ])
            ->get();

        $viewer = $request->user();

        $data = $resources->map(function (Resource $resource) use ($start, $end, $rangeStart, $rangeEnd, $viewer): array {
            $overlappingReservations = $resource->active_reservations
                ->filter(function (Reservation $reservation) use ($rangeStart, $rangeEnd): bool {
                    /** @var ReservationResource $pivot */
                    $pivot = $reservation->pivot;
                    $pivotStart = $pivot->start_time;
                    $pivotEnd = $pivot->end_time;

                    if ($pivotStart === null || $pivotEnd === null) {
                        return false;
                    }

                    // The SQL window can include both local offsets near a clock change.
                    return $pivotStart->lt($rangeEnd) && $pivotEnd->gt($rangeStart);
                })
                ->map(fn (Reservation $reservation): array => ResourcePresenter::reservationRow($reservation, $viewer))
                ->values()
                ->all();

            return [
                'id' => (string) $resource->id,
                ...ResourcePresenter::availability($resource, $start, $end, $viewer),
                'reservations' => $overlappingReservations,
            ];
        })->keyBy('id');

        return $this->jsonSuccess($data);
    }
}
