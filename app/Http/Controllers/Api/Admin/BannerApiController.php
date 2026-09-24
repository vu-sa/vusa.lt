<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexBannerRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Banner;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class BannerApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexBannerRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Banner::class);

        $query = fn (IndexBannerRequest $request) => $this->applyTanstackFilters(
            Banner::query()->with('tenant:id,shortname'),
            $request,
            $this->tableService,
            ['title'],
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'banners.read.padalinys',
            ]
        );
        $banners = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $banners->getCollection()->values(),
            'total' => $banners->total(),
            'per_page' => $banners->perPage(),
            'current_page' => $banners->currentPage(),
            'last_page' => $banners->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['is_active'], $query),
        ]);
    }
}
