<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Resource;
use App\Services\Reservations\ResourcePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class ResourceApiController extends ApiController
{
    /**
     * Lightweight resource detail used by the admin search preview pane.
     *
     * Returns the upcoming/active reservations and resource managers that are
     * not carried in the Typesense search document.
     *
     * @route GET /api/v1/admin/resources/{resource}/preview
     *
     * @routeName api.v1.admin.resources.preview
     */
    #[Authorize('view', 'resource')]
    public function preview(Resource $resource): JsonResponse
    {
        $activeReservations = $resource->active_reservations()
            ->orderByPivot('start_time')
            ->limit(5)
            ->get();

        $previousReservations = $resource->reservations()
            ->wherePivotIn('state', ['returned', 'rejected', 'cancelled'])
            ->wherePivot('end_time', '<', now())
            ->orderByPivot('end_time', 'desc')
            ->limit(3)
            ->get();

        $mapReservation = fn ($reservation) => ResourcePresenter::reservationRow($reservation, request()->user());

        return $this->jsonSuccess([
            'upcoming_reservations' => $activeReservations->map($mapReservation)->values(),
            'previous_reservations' => $previousReservations->map($mapReservation)->values(),
            'managers' => ResourcePresenter::managers($resource),
        ]);
    }
}
