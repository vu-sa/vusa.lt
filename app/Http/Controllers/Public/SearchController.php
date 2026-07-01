<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends PublicController
{
    public function index(Request $request)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('search');

        // Global content - use null for current tenant
        $seo = $this->shareAndReturnSEOObject(
            contentTenant: null,
            title: __('search.all_page_title'),
            description: __('search.all_page_description')
        );

        return Inertia::render('Public/Search', [
            'initialQuery' => $request->string('q')->toString(),
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
