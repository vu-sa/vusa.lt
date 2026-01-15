<?php

namespace App\Http\Controllers\Api;

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class NewsController extends ApiController
{
    /**
     * Get news for a tenant (public endpoint).
     */
    public function getTenantNews(string $lang, Tenant $tenant): JsonResponse
    {
        $news = News::query()
            ->where([['tenant_id', '=', $tenant->id], ['lang', $lang], ['draft', '=', 0]])
            ->where('publish_time', '<=', date('Y-m-d H:i:s'))
            ->orderBy('publish_time', 'desc')
            ->take(5)
            ->get(['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image']);

        return $this->jsonSuccess($news);
    }
}
