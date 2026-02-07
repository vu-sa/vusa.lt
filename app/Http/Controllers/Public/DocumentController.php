<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Document;
use App\Settings\DocumentSettings;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DocumentController extends PublicController
{
    public function index(DocumentSettings $documentSettings)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('documents');

        // Global content - use null for current tenant
        $seo = $this->shareAndReturnSEOObject(
            contentTenant: null,
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
            'importantContentTypes' => $documentSettings->getImportantContentTypes()->toArray(),
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
