<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Http\Requests\IndexPublicSearchRequest;
use Inertia\Inertia;

class SearchController extends PublicController
{
    public function index(IndexPublicSearchRequest $request)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL(
            $this->tenant->isMain() ? 'search' : 'tenant.search',
            $this->tenant->isMain() ? null : $this->subdomain,
        );

        // Global content - use null for current tenant
        $this->applyPageHead(
            contentTenant: null,
            title: __('search.all_page_title'),
            description: __('search.all_page_description')
        );

        return Inertia::render('Public/Search', [
            'tenantSwitchTarget' => 'same-page',
            'initialQuery' => (string) $request->validated('q'),
        ]);
    }
}
