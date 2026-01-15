<?php

namespace App\Http\Controllers\Api;

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends ApiController
{
    /**
     * Get news for a tenant (public endpoint).
     *
     * @route GET /api/v1/tenants/{tenant}/news
     *
     * @routeName api.v1.tenants.news.index
     */
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $lang = $request->query('lang', app()->getLocale());

        $news = News::query()
            ->where('tenant_id', $tenant->id)
            ->where('lang', $lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(5)
            ->get(['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image']);

        return $this->jsonSuccess($news);
    }
}
