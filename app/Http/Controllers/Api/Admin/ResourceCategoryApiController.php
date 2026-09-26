<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexResourceCategoryRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\ResourceCategory;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class ResourceCategoryApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexResourceCategoryRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', ResourceCategory::class);

        $categories = $this->applyTanstackFilters(
            ResourceCategory::query(),
            $request,
            $this->tableService,
            ['name'],
            ['applySortBeforePagination' => true],
        )->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $categories->getCollection()->map(fn (ResourceCategory $category): array => $category->toFullArray())->values(),
            'total' => $categories->total(),
            'per_page' => $categories->perPage(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
        ]);
    }
}
