<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Reservation;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class ReservationApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexReservationRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Reservation::class);

        $reservations = $this->applyTanstackFilters(
            Reservation::query()->with([
                'resources.tenant:id,shortname',
                'users:id,name,profile_photo_path',
            ]),
            $request,
            $this->tableService,
            ['name', 'description'],
        )->paginate($request->getPerPage());

        $items = $reservations->getCollection()->each(function (Reservation $reservation): void {
            $reservation->resources->each(function ($resource): void {
                $resource->pivot?->append('approvable');
            });
        })->values();

        return $this->jsonSuccess([
            'items' => $items,
            'total' => $reservations->total(),
            'per_page' => $reservations->perPage(),
            'current_page' => $reservations->currentPage(),
            'last_page' => $reservations->lastPage(),
        ]);
    }
}
