<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ApplyReservationIndexFilters;
use App\Actions\SerializeReservationsForTable;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Reservation;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class ReservationApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService, private ModelAuthorizer $authorizer) {}

    public function index(IndexReservationRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Reservation::class);

        $query = ApplyReservationIndexFilters::execute(
            Reservation::query()->with(SerializeReservationsForTable::EAGER_LOADS),
            $request,
            $request->user(),
            $this->authorizer,
        );

        $reservations = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['name', 'description'],
        )->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => SerializeReservationsForTable::execute($reservations->getCollection(), $request->user(), $this->authorizer),
            'total' => $reservations->total(),
            'per_page' => $reservations->perPage(),
            'current_page' => $reservations->currentPage(),
            'last_page' => $reservations->lastPage(),
        ]);
    }
}
