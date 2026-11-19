<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexTagRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Tag;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class TagApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    #[Authorize('viewAny', Tag::class)]
    public function index(IndexTagRequest $request): JsonResponse
    {
        $tags = $this->applyTanstackFilters(
            Tag::query(),
            $request,
            $this->tableService,
            ['name', 'description', 'alias'],
            ['applySortBeforePagination' => true],
        )->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $tags->getCollection()->map(fn (Tag $tag): array => $tag->toFullArray())->values(),
            'total' => $tags->total(),
            'per_page' => $tags->perPage(),
            'current_page' => $tags->currentPage(),
            'last_page' => $tags->lastPage(),
        ]);
    }
}
