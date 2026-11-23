<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildDutyIndexQuery;
use App\Actions\Duties\CreateDuty;
use App\Actions\SetDutyActive;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\UpdateRecordActivityRequest;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Requests\StoreDutyRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Services\DutyAccessService;
use App\Services\DutyPresenter;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class DutyApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function updateStatus(UpdateRecordActivityRequest $request, Duty $duty, SetDutyActive $update): JsonResponse
    {
        $update->execute($duty, $request->boolean('is_active'));

        return $this->jsonSuccess(['is_active' => $duty->fresh()->is_active], $this->entityMessage('updated', 'duty'));
    }

    #[Authorize('viewAny', Duty::class)]
    public function index(IndexDutyRequest $request, DutyAccessService $access): JsonResponse
    {
        $query = fn (IndexDutyRequest $request) => $this->applyTanstackFilters(
            BuildDutyIndexQuery::execute($request, $access),
            $request,
            $this->tableService,
            ['name', 'email'],
        );
        $duties = $query($request)->paginate($request->getPerPage());

        $this->appendForceDeleteBlockedReason($duties->getCollection(), $request);

        return $this->jsonSuccess([
            'items' => $duties->getCollection()->map(fn (Duty $duty): array => DutyPresenter::forIndex($duty, $request->user()))->values(),
            'total' => $duties->total(),
            'per_page' => $duties->perPage(),
            'current_page' => $duties->currentPage(),
            'last_page' => $duties->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['is_active', 'data_quality', 'duty_type_id', 'history'], $query),
        ]);
    }

    #[Authorize('create', Duty::class)]
    public function store(StoreDutyRequest $request, CreateDuty $create): JsonResponse
    {
        return $this->jsonCreated([
            'duty' => $create->execute($request->validated(), $request->user()),
        ], $this->entityMessage('created', 'duty'));
    }
}
