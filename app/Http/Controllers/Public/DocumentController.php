<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Document;
use Illuminate\Support\Carbon;
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
            title: __('Dokumentai').' - VU SA',
            description: 'VU SA dokumentai'
        );

        // Create cache key based on request parameters
        $requestParams = request()->all();
        $cacheKey = 'documents_'.md5(serialize($requestParams));

        $documentsData = Cache::tags(['documents'])
            ->remember($cacheKey, 7200, function () use ($requestParams) { // 2 hours TTL
                if ($requestParams === []) {
                    // No search parameters - use database query for better performance
                    $documents = Document::query()->with('institution.tenant')
                        ->where('is_active', true)
                        ->orderBy('document_date', 'desc');
                } else {
                    // Check if we should use Typesense or database search
                    $useTypesense = config('scout.driver') === 'typesense' &&
                                  config('scout.typesense.client-settings.api_key') &&
                                  ! in_array(config('scout.typesense.client-settings.api_key'), ['xyz', 'xyza'], true);

                    if ($useTypesense) {
                        // Build Typesense search with filters
                        $searchQuery = request()->q ?? '';
                        $builder = Document::search($searchQuery);

                        // Build filter conditions for Typesense
                        $filters = ['is_active:=true'];

                        // Content type filter
                        if (request()->has('contentTypes') && ! empty(request()->contentTypes)) {
                            $contentTypes = collect(request()->contentTypes)->map(fn ($type) => "`{$type}`")->implode(',');
                            $filters[] = "content_type:[{$contentTypes}]";
                        }

                        // Language filter
                        if (request()->has('language') && ! empty(request()->language)) {
                            $languages = collect(request()->language)->map(fn ($lang) => "`{$lang}`")->implode(',');
                            $filters[] = "language:[{$languages}]";
                        }

                        // Tenant filter (using tenant_shortname)
                        if (request()->has('tenants') && ! empty(request()->tenants)) {
                            $tenants = collect(request()->tenants)->map(fn ($tenant) => "`{$tenant}`")->implode(',');
                            $filters[] = "tenant_shortname:[{$tenants}]";
                        }

                        // Date range filter
                        if (request()->has('dateFrom') || request()->has('dateTo')) {
                            $dateFrom = request()->dateFrom ? intval(request()->dateFrom / 1000) : 0;
                            $dateTo = request()->dateTo ? intval(request()->dateTo / 1000) : 2147483647; // Max timestamp
                            $filters[] = "document_date:[{$dateFrom}..{$dateTo}]";
                        }

                        // Apply filters to search
                        $builder->options([
                            'filter_by' => implode(' && ', $filters),
                            'sort_by' => 'document_date:desc',
                        ]);

                        $documents = $builder;
                    } else {
                        // Fall back to database search with filters
                        $documents = Document::query()->with('institution.tenant')
                            ->where('is_active', true)
                            ->when(request()->has('q') && request()->q, function ($query) {
                                $search = request()->q;
                                $query->where(function ($q) use ($search) {
                                    $q->where('title', 'like', "%{$search}%")
                                        ->orWhere('summary', 'like', "%{$search}%");
                                });
                            })
                            ->when(request()->has('contentTypes') && ! empty(request()->contentTypes), function ($query) {
                                $query->whereIn('content_type', request()->contentTypes);
                            })
                            ->when(request()->has('language') && ! empty(request()->language), function ($query) {
                                $query->whereIn('language', request()->language);
                            })
                            ->when(request()->has('tenants') && ! empty(request()->tenants), function ($query) {
                                $query->whereHas('institution.tenant', function ($q) {
                                    $q->whereIn('shortname', request()->tenants);
                                });
                            })
                            ->when(request()->has('dateFrom') || request()->has('dateTo'), function ($query) {
                                $dateFrom = request()->dateFrom ? Carbon::parse(request()->dateFrom / 1000) : null;
                                $dateTo = request()->dateTo ? Carbon::parse(request()->dateTo / 1000) : null;

                                $query->when($dateFrom, function ($q) use ($dateFrom) {
                                    $q->where('document_date', '>=', $dateFrom);
                                })->when($dateTo, function ($q) use ($dateTo) {
                                    $q->where('document_date', '<=', $dateTo);
                                });
                            })
                            ->orderBy('document_date', 'desc');
                    }
                }

                $paginated = $documents->paginate(20);
                $collection = $paginated->getCollection()->append('is_in_effect')->load('institution');

                return [
                    'documents' => $paginated->setCollection($collection),
                    'contentTypes' => Document::query()->select('content_type')->whereNotNull('content_type')->distinct()->pluck('content_type')->sort()->values(),
                ];
            });

        return Inertia::render('Public/ShowDocuments', [
            'documents' => $documentsData['documents'],
            'allContentTypes' => $documentsData['contentTypes'],
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
