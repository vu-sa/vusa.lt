<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexBannerRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Banner;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class BannerApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexBannerRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Banner::class);

        $query = Banner::query()->with('tenant:id,shortname');

        $banners = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['title'],
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'banners.read.padalinys',
            ]
        )->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $banners->getCollection()->values(),
            'total' => $banners->total(),
            'per_page' => $banners->perPage(),
            'current_page' => $banners->currentPage(),
            'last_page' => $banners->lastPage(),
        ]);
    }
}
