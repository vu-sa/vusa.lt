<?php

namespace App\Http\Controllers\Api;

use App\Collections\NewsCollection;
use App\Http\Requests\Api\IndexPublicNewsRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class NewsController extends ApiController
{
    /**
     * Get news for a tenant (public endpoint).
     *
     * @route GET /api/v1/tenants/{tenant}/news
     *
     * @routeName api.v1.tenants.news.index
     */
    public function index(IndexPublicNewsRequest $request, Tenant $tenant): JsonResponse
    {
        $lang = $request->validated('lang') ?? app()->getLocale();

        $news = NewsCollection::getPublishedForTenant($tenant->id, $lang);

        return $this->jsonSuccess($news->toPublicArray());
    }
}
