<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;

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
    public function preview(Resource $resource): JsonResponse
    {
        $this->authorizeApi('view', $resource);

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

        $mapReservation = fn ($reservation) => [
            'id' => (string) $reservation->id,
            'name' => $reservation->name,
            'quantity' => (int) $reservation->pivot->quantity,
            'state' => (string) $reservation->pivot->state,
            'start_time' => $reservation->pivot->start_time?->timestamp,
            'end_time' => $reservation->pivot->end_time?->timestamp,
        ];

        return $this->jsonSuccess([
            'upcoming_reservations' => $activeReservations->map($mapReservation)->values(),
            'previous_reservations' => $previousReservations->map($mapReservation)->values(),
            'managers' => $resource->managers()
                ->map(fn ($user) => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'facebook_url' => $user->facebook_url,
                    'profile_photo_path' => $user->profile_photo_path,
                ])->values(),
        ]);
    }
}
