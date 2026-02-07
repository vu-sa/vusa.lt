<?php

namespace App\Http\Controllers;

use App\Actions\GetAliasSubdomainForPublic;
use App\Models\Navigation;
use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\SchemaOrg\Organization;

class PublicController extends Controller
{
    protected Tenant $tenant;

    protected string $subdomain;

    public function __construct()
    {
        /**
         * Every public page requires an 'alias', which is basically the shortname of a tenant.
         * Alias may decide in the controller, what kind of information is displayed.
         *  */
        [$alias, $subdomain] = GetAliasSubdomainForPublic::execute();

        // When we have the final alias, get the tenant that will be used in all of the public controllers
        $this->tenant = Tenant::where('alias', $alias)->first();

        // We also need to use the subdomain in the public controllers
        $this->subdomain = $subdomain;

        // Subdomain and alias won't be different, except when alias = 'vusa', then subdomain = 'www'
        Inertia::share('tenant', $this->tenant->only(['id', 'shortname', 'alias', 'type']) +
            ['subdomain' => $subdomain]
        );

        // Initialize otherLangURL as null by default - controllers can override this
        Inertia::share('otherLangURL', null);
    }

    protected function getBanners()
    {
        $cacheKey = "banners_{$this->tenant->id}";
        $banners = Cache::tags(['banners', "tenant_{$this->tenant->id}"])
            ->remember($cacheKey, 3600, function () {
                $banners = Tenant::where('alias', 'vusa')->first()
                    ->banners()
                    ->inRandomOrder()
                    ->where('is_active', 1)
                    ->get();

                if ($this->tenant->type !== 'pagrindinis') {
                    $tenantBanners = $this->tenant
                        ->banners()
                        ->inRandomOrder()
                        ->where('is_active', 1)
                        ->get();
                    $banners = $tenantBanners->merge($banners);
                }

                return $banners;
            });

        Inertia::share('tenant.banners', $banners);
    }

    protected function getTenantLinks()
    {
        $locale = app()->getLocale();
        $cacheKey = "tenant_links_{$this->tenant->id}_{$locale}";

        $quickLinks = Cache::tags(['quick_links', "tenant_{$this->tenant->id}", "locale_{$locale}"])
            ->remember($cacheKey, 3600, function () use ($locale) {
                return QuickLink::query()
                    ->where([
                        ['tenant_id', $this->tenant->id],
                        ['lang', $locale],
                    ])
                    ->orderBy('order')
                    ->get(['id', 'link', 'text', 'icon', 'is_important']);
            });

        Inertia::share('tenant.links', $quickLinks);
    }

    protected function getNavigation()
    {
        $locale = app()->getLocale();
        $cacheKey = "navigation_{$locale}";

        $navigation = Cache::tags(['navigation', "locale_{$locale}"])
            ->remember($cacheKey, 7200, function () use ($locale) {
                return Navigation::query()
                    ->where('lang', $locale)
                    ->orderBy('order')
                    ->get();
            });

        Inertia::share('navigation', $navigation);
    }

    // This is mostly used for default sharing, other cases likes pages and news link to other URLs
    protected function shareOtherLangURL($name, ?string $subdomain = null, $calendarId = null)
    {
        try {
            $otherLangURL = route($name, array_filter([
                'lang' => $this->getOtherLang(),
                'calendar' => $calendarId,
                'subdomain' => $subdomain,
            ]));

            Inertia::share('otherLangURL', $otherLangURL);
        } catch (\Exception $e) {
            // If route generation fails, don't share otherLangURL
            // This allows the LocaleButton to gracefully handle missing translations
            Inertia::share('otherLangURL', null);
        }
    }

    protected function getOtherLang()
    {
        return app()->getLocale() === 'lt' ? 'en' : 'lt';
    }

    /**
     * Get the subdomain for a tenant.
     *
     * @param  Tenant|null  $tenant  The tenant to get subdomain for (defaults to current tenant)
     */
    protected function getSubdomainForTenant(?Tenant $tenant = null): string
    {
        $tenant = $tenant ?? $this->tenant;

        // Main 'vusa' tenant uses 'www' subdomain
        if ($tenant->alias === 'vusa') {
            return 'www';
        }

        return $tenant->alias;
    }

    /**
     * Generate a route URL for a specific tenant's subdomain.
     *
     * Uses Laravel's route() helper to generate proper URLs. Any parameters
     * not matching route parameters will be added as query string.
     *
     * @param  string  $routeName  The route name
     * @param  array  $parameters  Route parameters (extra params become query string)
     * @param  Tenant|null  $tenant  The tenant to use for subdomain (defaults to current tenant)
     */
    protected function tenantRoute(string $routeName, array $parameters = [], ?Tenant $tenant = null): string
    {
        $subdomain = $this->getSubdomainForTenant($tenant);

        // Merge subdomain with provided parameters
        // Note: route() automatically adds non-route params as query string
        return route($routeName, array_merge([
            'subdomain' => $subdomain,
        ], $parameters));
    }

    /**
     * Generate the canonical URL for the current page.
     *
     * Uses Laravel's Route facade to get current route name and parameters,
     * then rebuilds the URL with the content owner's subdomain.
     *
     * @param  Tenant|null  $contentTenant  The tenant that owns the content (for proper canonical URL)
     * @param  bool  $includeQueryString  Whether to include query parameters (for pagination, etc.)
     */
    protected function getCanonicalUrl(?Tenant $contentTenant = null, bool $includeQueryString = false): string
    {
        $currentRoute = Route::current();
        $routeName = Route::currentRouteName();

        if (! $currentRoute || ! $routeName) {
            // Fallback to current URL if no named route
            return request()->url();
        }

        // Get current route parameters and replace subdomain with content tenant's
        $parameters = $currentRoute->parameters();

        // Include query string parameters if needed
        if ($includeQueryString) {
            $queryParams = request()->query();
            $parameters = array_merge($parameters, $queryParams);
        }

        return $this->tenantRoute($routeName, $parameters, $contentTenant);
    }

    /**
     * Regenerate a route URL with a different tenant's subdomain.
     *
     * Takes a route name and parameters (typically from another route generation)
     * and rebuilds with the specified tenant's subdomain.
     *
     * @param  string  $routeName  The route name to generate
     * @param  array  $parameters  Route parameters
     * @param  Tenant|null  $tenant  The tenant to use for subdomain (defaults to current tenant)
     */
    protected function regenerateRouteForTenant(string $routeName, array $parameters, ?Tenant $tenant = null): string
    {
        return $this->tenantRoute($routeName, $parameters, $tenant);
    }

    /**
     * Replace the subdomain in an existing URL with a tenant's subdomain.
     *
     * This is used for normalizing URLs that were already generated (like otherLangURL)
     * to use the content owner's subdomain instead of the accessing subdomain.
     *
     * @param  string  $url  The URL to modify
     * @param  Tenant|null  $tenant  The tenant to use for subdomain (defaults to current tenant)
     */
    protected function replaceSubdomainInUrl(string $url, ?Tenant $tenant = null): string
    {
        $targetSubdomain = $this->getSubdomainForTenant($tenant);
        $parsedUrl = parse_url($url);

        if (! $parsedUrl || ! isset($parsedUrl['host'])) {
            return $url;
        }

        // Get the base domain from config (e.g., 'vusa.lt' from 'https://www.vusa.lt')
        $baseUrl = config('app.url');
        $parsedBase = parse_url($baseUrl);
        $baseDomain = $parsedBase['host'] ?? 'vusa.lt';

        // Remove any subdomain prefix from base domain
        if (str_starts_with($baseDomain, 'www.')) {
            $baseDomain = substr($baseDomain, 4);
        }

        // Build new URL with target subdomain
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $newUrl = $scheme.'://'.$targetSubdomain.'.'.$baseDomain;

        if (isset($parsedUrl['port'])) {
            $newUrl .= ':'.$parsedUrl['port'];
        }

        if (isset($parsedUrl['path'])) {
            $newUrl .= $parsedUrl['path'];
        }

        if (isset($parsedUrl['query'])) {
            $newUrl .= '?'.$parsedUrl['query'];
        }

        return $newUrl;
    }

    /**
     * Share pagination SEO metadata for rel=next/prev links.
     *
     * Uses Laravel's route() helper to generate proper paginated URLs
     * with the content owner's subdomain.
     *
     * @param  LengthAwarePaginator  $paginator  The paginator instance
     * @param  Tenant|null  $contentTenant  The tenant that owns the content (for proper canonical URLs)
     */
    protected function sharePaginationSeoMeta(LengthAwarePaginator $paginator, ?Tenant $contentTenant = null): void
    {
        $paginationSeo = [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'prevPageUrl' => null,
            'nextPageUrl' => null,
        ];

        $currentRoute = Route::current();
        $routeName = Route::currentRouteName();

        if (! $currentRoute || ! $routeName) {
            Inertia::share('seo.pagination', $paginationSeo);

            return;
        }

        // Get current route parameters and query params (excluding page)
        $routeParams = $currentRoute->parameters();
        $queryParams = request()->except(['page']);

        if ($paginator->currentPage() > 1) {
            $prevParams = array_merge($routeParams, $queryParams);
            // Only add page param if not going to page 1
            if ($paginator->currentPage() > 2) {
                $prevParams['page'] = $paginator->currentPage() - 1;
            }
            $paginationSeo['prevPageUrl'] = $this->tenantRoute($routeName, $prevParams, $contentTenant);
        }

        if ($paginator->currentPage() < $paginator->lastPage()) {
            $nextParams = array_merge($routeParams, $queryParams, [
                'page' => $paginator->currentPage() + 1,
            ]);
            $paginationSeo['nextPageUrl'] = $this->tenantRoute($routeName, $nextParams, $contentTenant);
        }

        Inertia::share('seo.pagination', $paginationSeo);
    }

    /**
     * Share and return SEO object with proper canonical URL based on content ownership.
     *
     * @param  Tenant|null  $contentTenant  The tenant that owns the content (for proper canonical URL)
     * @param  mixed  ...$args  Additional arguments for SEOData
     */
    protected function shareAndReturnSEOObject(?Tenant $contentTenant = null, ...$args)
    {
        // Generate canonical URL using the content owner's subdomain
        // This ensures content is always canonicalized to its owner's subdomain
        $canonicalUrl = $this->getCanonicalUrl(contentTenant: $contentTenant, includeQueryString: true);

        // If canonical_url is not already set in args, add it
        $hasCanonicalUrl = false;
        foreach ($args as $key => $value) {
            if ($key === 'canonical_url' && $value !== null) {
                $hasCanonicalUrl = true;
                break;
            }
        }

        if (! $hasCanonicalUrl) {
            $args['canonical_url'] = $canonicalUrl;
        }

        $seoData = new SEOData(...$args);

        $seoDataArray = seo(clone $seoData);

        // Use named array with keys that use object classes
        $associatedArray = collect($seoDataArray->tags)->mapWithKeys(function ($tag) {
            return [get_class($tag) => $tag];
        });

        // Add hreflang tags for bilingual content
        $currentLocale = app()->getLocale();
        $otherLocale = $currentLocale === 'lt' ? 'en' : 'lt';

        // Use canonical URL for hreflang (content owner's subdomain)
        $currentUrl = $canonicalUrl;

        // Generate hreflang URLs for current page
        $hreflangTags = [];

        // Current language URL
        $hreflangTags[] = sprintf(
            '<link rel="alternate" hreflang="%s" href="%s" />',
            $currentLocale,
            $currentUrl
        );

        // Other language URL (if available via shared otherLangURL)
        // Note: otherLangURL is already generated via route() with correct subdomain,
        // but we need to regenerate it with the content tenant's subdomain
        $otherLangURL = Inertia::getShared('otherLangURL');
        $normalizedOtherLangUrl = null;

        if ($otherLangURL) {
            // Parse the URL to extract route info and regenerate with content tenant
            // Since otherLangURL was generated via route(), we can use URL replacement
            $normalizedOtherLangUrl = $this->replaceSubdomainInUrl($otherLangURL, $contentTenant);
            $hreflangTags[] = sprintf(
                '<link rel="alternate" hreflang="%s" href="%s" />',
                $otherLocale,
                $normalizedOtherLangUrl
            );
        }

        // x-default to Lithuanian (primary language) - use content owner's subdomain
        $normalizedDefaultUrl = $currentLocale === 'lt' ? $currentUrl : ($normalizedOtherLangUrl ?? $currentUrl);
        $hreflangTags[] = sprintf(
            '<link rel="alternate" hreflang="x-default" href="%s" />',
            $normalizedDefaultUrl
        );

        // Share hreflang tags
        Inertia::share('seo.hreflang', $hreflangTags);

        // Add structured data schemas
        $schemas = $this->getStructuredDataSchemas();
        Inertia::share('schemas', $schemas);

        // NOTE: seo() modifies the object in place, so we need to clone it
        Inertia::share('seo.tags', $associatedArray);

        $image = config('app.url').config('seo.image.fallback');

        if (! empty($seoData->image)) {
            if (substr($seoData->image, 0, 4) === 'http') {
                $image = $seoData->image;
            } else {
                $storedImage = Storage::get(str_replace('uploads', 'public', $seoData->image));
                if ($storedImage !== null) {
                    $image = $seoData->image;
                } else {
                    $image = config('seo.image.fallback');
                }
            }
        }

        // HACK: Share image separately, because it's hard to consume directly
        // But maybe it's because of secure_url not working in localhost
        Inertia::share('seo.image', $image);

        return $seoData;
    }

    protected function getStructuredDataSchemas()
    {
        $locale = app()->getLocale();
        $cacheKey = "structured_schemas_{$locale}";

        return Cache::tags(['schemas', "locale_{$locale}"])
            ->remember($cacheKey, 86400, function () use ($locale) { // 24 hours TTL
                $baseUrl = config('app.url');

                // Organization schema for VU SA
                $organizationSchema = (new Organization)
                    ->name($locale === 'lt' ? 'Vilniaus universiteto Studentų atstovybė' : 'Vilnius University Students\' Representation')
                    ->alternateName('VU SA')
                    ->url($baseUrl)
                    ->logo($baseUrl.'/images/photos/vusa.jpg')
                    ->description($locale === 'lt'
                        ? 'VU SA - visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija, atstovaujanti Vilniaus universiteto studentų interesams.'
                        : 'VU SA - a public, non-profit, non-political, expert educational organization representing the interests of Vilnius University students.'
                    )
                    ->sameAs([
                        'https://www.facebook.com/VUSA.LT',
                        'https://www.instagram.com/vusa.lt',
                        'https://www.linkedin.com/company/vusa-lt',
                    ]);

                return [
                    $organizationSchema,
                ];
            });
    }
}
