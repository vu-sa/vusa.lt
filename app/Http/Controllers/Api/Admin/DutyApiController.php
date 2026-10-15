<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildDutyIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class DutyApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService, private ModelAuthorizer $authorizer) {}

    public function index(IndexDutyRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Duty::class);

        $query = fn (IndexDutyRequest $request) => $this->applyTanstackFilters(
            BuildDutyIndexQuery::execute($request, $this->authorizer),
            $request,
            $this->tableService,
            ['name', 'email'],
        );
        $duties = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $duties->getCollection()->map(fn (Duty $duty): array => $duty->append('force_delete_blocked_reason')->toFullArray())->values(),
            'total' => $duties->total(),
            'per_page' => $duties->perPage(),
            'current_page' => $duties->currentPage(),
            'last_page' => $duties->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['data_quality'], $query),
        ]);
    }
}
