<?php

namespace App\Http\Controllers;

use App\Actions\GetAliasSubdomainForPublic;
use App\Models\Navigation;
use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\WebSite;

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
        Inertia::share('otherLangURL',
            route($name,
                [
                    'lang' => $this->getOtherLang(),
                    'calendar' => $calendarId,
                    'subdomain' => $subdomain,
                ])
        );
    }

    protected function getOtherLang()
    {
        return app()->getLocale() === 'lt' ? 'en' : 'lt';
    }

    protected function shareAndReturnSEOObject(...$args)
    {
        $seoData = new SEOData(...$args);

        $seoDataArray = seo(clone $seoData);

        // Use named array with keys that use object classes
        $associatedArray = collect($seoDataArray->tags)->mapWithKeys(function ($tag) {
            return [get_class($tag) => $tag];
        });

        // Add hreflang tags for bilingual content
        $currentLocale = app()->getLocale();
        $otherLocale = $currentLocale === 'lt' ? 'en' : 'lt';
        $currentUrl = request()->url();

        // Generate hreflang URLs for current page
        $hreflangTags = [];

        // Current language URL
        $hreflangTags[] = sprintf(
            '<link rel="alternate" hreflang="%s" href="%s" />',
            $currentLocale,
            $currentUrl
        );

        // Other language URL (if available via shared otherLangURL)
        $otherLangURL = Inertia::getShared('otherLangURL');
        if ($otherLangURL) {
            $hreflangTags[] = sprintf(
                '<link rel="alternate" hreflang="%s" href="%s" />',
                $otherLocale,
                $otherLangURL
            );
        }

        // x-default to Lithuanian (primary language)
        $defaultUrl = $currentLocale === 'lt' ? $currentUrl : ($otherLangURL ?? $currentUrl);
        $hreflangTags[] = sprintf(
            '<link rel="alternate" hreflang="x-default" href="%s" />',
            $defaultUrl
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

                // WebSite schema with search functionality
                $websiteSchema = (new WebSite)
                    ->name($locale === 'lt' ? 'VU SA svetainė' : 'VU SA website')
                    ->url($baseUrl)
                    ->description($locale === 'lt'
                        ? 'Oficiali Vilniaus universiteto Studentų atstovybės svetainė'
                        : 'Official website of Vilnius University Students\' Representation'
                    )
                    ->potentialAction([
                        'SearchAction' => [
                            '@type' => 'SearchAction',
                            'target' => [
                                '@type' => 'EntryPoint',
                                'urlTemplate' => $baseUrl.'/search?q={search_term_string}',
                            ],
                            'query-input' => 'required name=search_term_string',
                        ],
                    ]);

                return [
                    $organizationSchema,
                    $websiteSchema,
                ];
            });
    }
}
