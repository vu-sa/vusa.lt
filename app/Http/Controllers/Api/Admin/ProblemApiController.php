<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildProblemIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexProblemRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Problem;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class ProblemApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(
        private TanstackTableService $tableService,
        private ModelAuthorizer $authorizer
    ) {}

    public function index(IndexProblemRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Problem::class);

        $query = fn (IndexProblemRequest $request) => $this->applyTanstackFilters(
            BuildProblemIndexQuery::execute($request, $this->authorizer, $this->tableService),
            $request,
            $this->tableService,
            ['title', 'description'],
            ['applySortBeforePagination' => true]
        );
        $problems = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $problems->getCollection()->map(fn (Problem $problem): array => $problem->toFullArray())->values(),
            'total' => $problems->total(),
            'per_page' => $problems->perPage(),
            'current_page' => $problems->currentPage(),
            'last_page' => $problems->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['status', 'category', 'institution', 'tenant.id'], $query),
        ]);
    }
}
