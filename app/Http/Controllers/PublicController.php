<?php

namespace App\Http\Controllers;

use App\Actions\GetAliasSubdomainForPublic;
use App\Actions\GetPublicEditLink;
use App\Http\Traits\ResolvesPublicContent;
use App\Models\Navigation;
use App\Models\QuickLink;
use App\Models\Tenant;
use App\Support\LocalizedRouteSlugs;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Laravel\Head\Facades\Head;
use Spatie\SchemaOrg\BreadcrumbList;
use Spatie\SchemaOrg\ListItem;
use Spatie\SchemaOrg\Organization;

class PublicController extends Controller
{
    use ResolvesPublicContent;

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
        $tenant = Tenant::where('alias', $alias)->first();

        // An unrecognized Host (e.g. the catch-all {permalink} route matching a request whose
        // domain isn't a known tenant subdomain) must 404, not crash with a TypeError trying to
        // assign null to the non-nullable $tenant property below.
        abort_if($tenant === null, 404, 'Tenant not found');

        $this->tenant = $tenant;

        // We also need to use the subdomain in the public controllers
        $this->subdomain = $subdomain;

        // Subdomain and alias won't be different, except when alias = 'vusa', then subdomain = 'www'
        Inertia::share('tenant', $this->tenant->only(['id', 'shortname', 'alias', 'type']) +
            ['subdomain' => $subdomain]
        );

        // Initialize otherLangURL as null by default - controllers can override this
        Inertia::share('otherLangURL', null);

        // Same for the admin edit link — only controllers that show an editable record set it
        Inertia::share('publicEditLink', null);
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

                if (! $this->tenant->isMain()) {
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
            ->remember($cacheKey, 3600, fn () => QuickLink::query()
                ->where([
                    ['tenant_id', $this->tenant->id],
                    ['lang', $locale],
                ])
                ->orderBy('order')
                ->get(['id', 'link', 'text', 'icon', 'is_important']));

        Inertia::share('tenant.links', $quickLinks);
    }

    protected function getNavigation()
    {
        $locale = app()->getLocale();
        $cacheKey = "navigation_{$locale}";

        $navigation = Cache::tags(['navigation', "locale_{$locale}"])
            ->remember($cacheKey, 7200, fn () => Navigation::query()
                ->where('lang', $locale)
                ->orderBy('order')
                ->get());

        Inertia::share('navigation', $navigation);
    }

    /**
     * Share the current page's URL in the other language, for the language toggle.
     *
     * Routes with a localized segment ("/lt/dokumentai" vs "/en/documents") get the other
     * language's slug filled in by {@see LocalizedRouteSlugs::route()} — `URL::defaults()`
     * always describes the language being rendered, so it cannot serve this.
     *
     * Pages whose content differs per language (news, pages) share their own URL instead.
     */
    protected function shareOtherLangURL($name, ?string $subdomain = null, $calendarId = null)
    {
        try {
            $otherLangURL = LocalizedRouteSlugs::route($name, array_filter([
                'calendar' => $calendarId,
                'subdomain' => $subdomain,
            ]), $this->getOtherLang());

            Inertia::share('otherLangURL', $otherLangURL);
        } catch (\Exception $exception) {
            // A missing route means the toggle silently disappears, which is how two pages
            // pointing at routes that no longer exist went unnoticed. Report it outside
            // production so it surfaces, and still let LocaleButton hide itself.
            if (! app()->isProduction()) {
                report($exception);
            }

            Inertia::share('otherLangURL', null);
        }
    }

    protected function getOtherLang()
    {
        return app()->getLocale() === 'lt' ? 'en' : 'lt';
    }

    /**
     * Share the admin edit link for the record this public page displays. Null for
     * guests and unauthorized users — GetPublicEditLink short-circuits both, so
     * anonymous traffic never reaches policy resolution.
     *
     * Call this outside any Cache::remember() closure: the result depends on the
     * current user and must never be cached.
     */
    protected function sharePublicEditLink(Model $model): void
    {
        Inertia::share('publicEditLink', GetPublicEditLink::execute($model));
    }

    /**
     * Get the subdomain for a tenant.
     *
     * @param  Tenant|null  $tenant  The tenant to get subdomain for (defaults to current tenant)
     */
    protected function getSubdomainForTenant(?Tenant $tenant = null): string
    {
        return ($tenant ?? $this->tenant)->subdomain();
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

        $uri = Uri::of($url);

        if (! $uri->host()) {
            return $url;
        }

        // Get the base domain from config (e.g., 'vusa.lt' from 'https://www.vusa.lt')
        $baseDomain = Uri::of(config('app.url'))->host() ?? 'vusa.lt';

        // Remove any subdomain prefix from base domain
        if (str_starts_with($baseDomain, 'www.')) {
            $baseDomain = substr($baseDomain, 4);
        }

        // withHost() preserves scheme, port, path, query and fragment
        return (string) $uri->withHost($targetSubdomain.'.'.$baseDomain);
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
        $currentRoute = Route::current();
        $routeName = Route::currentRouteName();

        if (! $currentRoute || ! $routeName) {
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
            Head::link('prev', $this->tenantRoute($routeName, $prevParams, $contentTenant));
        }

        if ($paginator->currentPage() < $paginator->lastPage()) {
            $nextParams = array_merge($routeParams, $queryParams, [
                'page' => $paginator->currentPage() + 1,
            ]);
            Head::link('next', $this->tenantRoute($routeName, $nextParams, $contentTenant));
        }
    }

    /**
     * Apply page-specific head metadata (title, description, canonical, Open Graph, hreflang, …)
     * via Laravel Head, using the content owner's subdomain for URL generation.
     *
     * @param  Tenant|null  $contentTenant  The tenant that owns the content (for proper canonical/hreflang URLs)
     * @param  string|null  $titleSuffix  Overrides the derived " - <tenant shortname>" suffix; pass '' to
     *                                    suppress it entirely, or a string (e.g. an institution name) to replace it
     */
    protected function applyPageHead(
        ?Tenant $contentTenant = null,
        ?string $title = null,
        ?string $titleSuffix = null,
        ?string $description = null,
        ?string $image = null,
        ?string $author = null,
        ?string $robots = null,
        ?CarbonInterface $publishedTime = null,
        ?CarbonInterface $modifiedTime = null,
        ?string $canonicalUrl = null,
    ): void {
        // Generate canonical URL using the content owner's subdomain.
        // This ensures content is always canonicalized to its owner's subdomain.
        $canonicalUrl ??= $this->getCanonicalUrl(contentTenant: $contentTenant, includeQueryString: true);

        Head::canonical($canonicalUrl);

        if ($title !== null) {
            $suffix = $titleSuffix ?? ' - '.($contentTenant ?? $this->tenant)->shortname;

            $suffix === '' ? Head::title($title) : Head::title($title, suffix: $suffix);
        }

        if ($description !== null) {
            Head::description($description);
        }

        Head::ogImage($this->resolveOgImageUrl($image));

        if ($author !== null) {
            Head::meta('author', $author);
        }

        if ($robots !== null) {
            Head::robots($robots);
        }

        if ($publishedTime !== null) {
            Head::meta('article:published_time', $publishedTime->toIso8601String());
        }

        if ($modifiedTime !== null) {
            Head::meta('article:modified_time', $modifiedTime->toIso8601String());
        }

        $this->shareHreflangAlternates($contentTenant, $canonicalUrl);

        // Add structured data schemas (rendered in Blade via $JSONLD_Schemas / $page['props']['schemas'])
        Inertia::share('schemas', $this->getStructuredDataSchemas());
    }

    /**
     * Register hreflang alternate links for bilingual content, using the content owner's subdomain.
     */
    private function shareHreflangAlternates(?Tenant $contentTenant, string $canonicalUrl): void
    {
        $currentLocale = app()->getLocale();
        $otherLocale = $currentLocale === 'lt' ? 'en' : 'lt';

        // Use canonical URL for hreflang (content owner's subdomain)
        $currentUrl = $canonicalUrl;

        $alternates = [$currentLocale => $currentUrl];

        // Note: otherLangURL is already generated via route() with correct subdomain,
        // but we need to regenerate it with the content tenant's subdomain
        $otherLangURL = Inertia::getShared('otherLangURL');
        $normalizedOtherLangUrl = null;

        if ($otherLangURL) {
            // Parse the URL to extract route info and regenerate with content tenant
            // Since otherLangURL was generated via route(), we can use URL replacement
            $normalizedOtherLangUrl = $this->replaceSubdomainInUrl($otherLangURL, $contentTenant);
            $alternates[$otherLocale] = $normalizedOtherLangUrl;
        }

        // x-default to Lithuanian (primary language) - use content owner's subdomain
        $alternates['x-default'] = $currentLocale === 'lt' ? $currentUrl : ($normalizedOtherLangUrl ?? $currentUrl);

        Head::alternates($alternates);
    }

    /**
     * Resolve the Open Graph image URL, falling back to the site default when no image is
     * given or the referenced upload no longer exists in storage.
     */
    private function resolveOgImageUrl(?string $image): string
    {
        $fallback = config('app.url').'/images/photos/vusa.jpg';

        if (empty($image)) {
            return $fallback;
        }

        if (str_starts_with($image, 'http')) {
            return $image;
        }

        // Confirm the uploaded image still exists in storage before using it —
        // guards against stale paths left behind by deleted uploads.
        $storedImage = Storage::get(str_replace('uploads', 'public', $image));

        return $storedImage !== null ? $image : $fallback;
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
                    ->sameAs(array_values(config('vusa.social')));

                return [
                    $organizationSchema,
                ];
            });
    }

    /**
     * Generate BreadcrumbList structured data from breadcrumb array.
     *
     * @param  array  $breadcrumbs  Array of ['name' => string, 'url' => string]
     */
    protected function getBreadcrumbSchema(array $breadcrumbs): BreadcrumbList
    {
        $items = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $items[] = (new ListItem)
                ->position($position++)
                ->name($crumb['name'])
                ->item($crumb['url']);
        }

        return (new BreadcrumbList)
            ->itemListElement($items);
    }
}
