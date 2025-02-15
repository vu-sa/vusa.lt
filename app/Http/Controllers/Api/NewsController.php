<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Tenant;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getTenantNews($lang, Tenant $tenant)
    {
        $news = News::query()->where([['tenant_id', '=', $tenant->id], ['lang', $lang], ['draft', '=', 0]])
            ->where('publish_time', '<=', date('Y-m-d H:i:s'))
            ->orderBy('publish_time', 'desc')
            ->take(4)
            ->get(['id', 'title', 'lang', 'publish_time', 'permalink', 'image']);

        return response()->json($news);
    }
}
