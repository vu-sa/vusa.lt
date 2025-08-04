<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Document;
use App\Services\Typesense\TypesenseManager;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DocumentController extends PublicController
{
    public function index()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('documents');

        $seo = $this->shareAndReturnSEOObject(
            title: __('search.document_page_title'),
            description: __('search.document_page_description')
        );

        // Since the frontend now handles search via Typesense directly,
        // we only need to provide static data for page initialization
        $staticData = Cache::tags(['documents'])
            ->remember('documents_static_data', 7200, function () { // 2 hours TTL
                return [
                    'contentTypes' => Document::query()
                        ->select('content_type')
                        ->whereNotNull('content_type')
                        ->distinct()
                        ->pluck('content_type')
                        ->sort()
                        ->values(),
                ];
            });

        return Inertia::render('Public/ShowDocuments', [
            'allContentTypes' => $staticData['contentTypes'],
            'typesenseConfig' => TypesenseManager::getFrontendConfig(),
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
