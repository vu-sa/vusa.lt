<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexTagRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Tag;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class TagApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexTagRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Tag::class);

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
