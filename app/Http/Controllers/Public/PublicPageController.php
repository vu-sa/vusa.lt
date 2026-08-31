<?php

namespace App\Http\Controllers\Public;

use App\Actions\GetPublicMeetingDocuments;
use App\Collections\NewsCollection;
use App\Enums\TenantType;
use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\Type;
use App\Services\LocationGeocoder;
use App\Services\ResourceServices\InstitutionService;
use App\Settings\FormSettings;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicPageController extends PublicController
{
    protected function getEventsForCalendar()
    {
        $locale = app()->getLocale();
        $cacheKey = "calendar_events_{$locale}";

        return Cache::tags(['calendar', "locale_{$locale}"])
            ->remember($cacheKey, 1800, function () use ($locale) { // 30 minutes TTL
                if ($locale === 'en') {
                    return Calendar::query()->with(['category', 'media'])->where('is_international', true)->where('is_draft', false)
                        ->orderBy('date', 'desc')->take(100)->get()->map(fn ($event) => [
                            ...$event->toArray(),
                            'images' => $event->getMedia('images'),
                            'googleLink' => $event->googleLink(),
                        ]);
                } else {
                    return Calendar::query()->with(['category', 'media'])->where('is_draft', false)
                        ->orderBy('date', 'desc')->take(100)->get()->map(fn ($event) => [
                            ...$event->toArray(),
                            'images' => $event->getMedia('images'),
                            'googleLink' => $event->googleLink(),
                        ]);
                }
            });
    }

    public function home()
    {
        // Get shared data (these are cached internally)
        $this->getBanners();
        $this->getTenantLinks();
        $this->getNavigation();

        // Share other language URL for locale switching
        $this->shareOtherLangURL('home', $this->subdomain);

        // Cache the homepage-specific content
        $locale = app()->getLocale();
        $cacheKey = "homepage_content_{$this->tenant->id}_{$locale}";

        $content = Cache::tags(['homepage', "tenant_{$this->tenant->id}", "locale_{$locale}"])
            ->remember($cacheKey, 3600, function () {
                // Check if tenant has content with actual content parts
                $tenantContent = $this->tenant->content;

                // If no content or content has no parts, fall back to main tenant
                if (! $tenantContent || $tenantContent->parts->isEmpty()) {
                    return Tenant::main()?->content;
                }

                return $tenantContent;
            });

        // Fetch news for homepage to enable LCP image preloading (eliminates API waterfall)
        $newsCacheKey = "homepage_news_{$this->tenant->id}_{$locale}";

        // Only authenticated users pay for edit-link resolution. The target is whoever's
        // content is actually shown — subdomains without their own content show main's.
        if (Auth::check()) {
            // @phpstan-ignore nullsafe.neverNull (main tenant / its content can be null at runtime)
            $this->sharePublicEditLink($content?->tenant ?? $this->tenant);
        }

        $news = Cache::tags(['news', "tenant_{$this->tenant->id}", "locale_{$locale}"])
            ->remember($newsCacheKey, 1800, fn () => NewsCollection::getPublishedForTenant(
                $this->tenant->id,
                $locale
            )->toPublicArray());

        // Fetch calendar events for homepage (reduces API calls)
        $calendarEvents = $this->getEventsForCalendar();

        $this->applyPageHead(contentTenant: $this->tenant, title: __('Pagrindinis puslapis'));

        // Get first news image URL for LCP preload hint
        $firstNewsImageUrl = $news[0]['image'] ?? null;

        return Inertia::render('Public/HomePage', [
            'content' => $content,
            // `news`/`calendarEvents` stay as-is (HomePage's LCP tuning is built on this
            // exact prop shape); `resolvedParts` only carries the newer dynamic types
            // (link-list, event-list) a homepage content block might use.
            'resolvedParts' => (object) $this->resolveContentParts($content),
            'news' => $news,
            'calendarEvents' => $calendarEvents,
            'firstNewsImageUrl' => $firstNewsImageUrl,
        ]);
    }

    public function page()
    {
        // HACK: At first, since for PKP we want to redirect old pages to contacts page, we check in this function
        $pkps = (new InstitutionService)->getInstitutionsByTypeSlug('pkp');
        $institution = $pkps->firstWhere('alias', request()->permalink);

        if ($institution) {
            return redirect()->route('contacts.alias', ['subdomain' => $this->subdomain, 'lang' => app()->getLocale(), 'institution' => request()->permalink]);
        }

        // Continue with normal page rendering

        $this->getBanners();
        $this->getTenantLinks();

        // Cache the page data
        $locale = app()->getLocale();
        $cacheKey = "page_content_{$this->tenant->id}_{$locale}_".md5(request()->permalink);

        $pageData = Cache::tags(['pages', "tenant_{$this->tenant->id}", "locale_{$locale}"])
            ->remember($cacheKey, 3600, function () {
                $page = Page::query()->where([
                    ['permalink', '=', request()->permalink],
                    ['tenant_id', '=', $this->tenant->id],
                    ['is_active', '=', true],
                ])->first();

                if ($page === null) {
                    return null;
                }

                $navigation_item = Navigation::query()->where('name', $page->title)->first();
                $other_lang_page = $page->getOtherLanguage();

                return [
                    'page' => $page,
                    'navigation_item' => $navigation_item,
                    'other_lang_page' => $other_lang_page,
                ];
            });

        if ($pageData === null) {
            abort(404);
        }

        $page = $pageData['page'];
        $navigation_item = $pageData['navigation_item'];
        $other_lang_page = $pageData['other_lang_page'];

        // Outside the page cache above — depends on the current user.
        $this->sharePublicEditLink($page);

        Inertia::share('otherLangURL', $other_lang_page ? route(
            'page',
            [
                'subdomain' => $this->subdomain,
                'lang' => $other_lang_page->lang,
                'permalink' => $other_lang_page->permalink,
            ]
        ) : null);

        // Get description for SEO from first tiptap element
        // Use the page's tenant for proper canonical URL
        $this->applyPageHead(
            contentTenant: $page->tenant,
            title: $page->title,
            description: ContentHelper::getDescriptionForSeo($page),
        );

        // Generate breadcrumb schema
        $locale = app()->getLocale();
        $breadcrumbs = [
            [
                'name' => $locale === 'lt' ? 'Pradžia' : 'Home',
                'url' => route('home', ['subdomain' => $this->subdomain, 'lang' => $locale]),
            ],
        ];

        // Add category if exists
        if ($page->category) {
            $breadcrumbs[] = [
                'name' => $page->category->name,
                'url' => route('category', [
                    'subdomain' => $this->subdomain,
                    'lang' => $locale,
                    'category' => $page->category->alias,
                ]),
            ];
        }

        // Add current page
        $breadcrumbs[] = [
            'name' => $page->title,
            'url' => route('page', [
                'subdomain' => $this->subdomain,
                'lang' => $locale,
                'permalink' => $page->permalink,
            ]),
        ];

        return Inertia::render('Public/ContentPage', [
            'navigationItemId' => $navigation_item?->id,
            // Server-resolved dynamic blocks (link-list, event-list, the news/calendar
            // bridge) — outside the page cache above, since resolution can be
            // time-relative (`latest`/`upcoming` modes) while that cache is not.
            'resolvedParts' => (object) $this->resolveContentParts($page->content),
            'page' => [
                ...$page->only('id', 'title', 'lang', 'category', 'tenant', 'permalink', 'other_lang_id', 'layout', 'show_table_of_contents', 'show_title', 'show_breadcrumbs', 'highlights', 'featured_image', 'meta_description', 'last_edited_at', 'updated_at'),
                'content' => $page->content,
                /* 'content' => [ */
                /*    ...$page->content->toArray(), */
                /*    'parts' => $page->content->parts->map(function ($part) { */
                /*        return [ */
                /*            ...$part->parseTipTapElements()->toArray(), */
                /*        ]; */
                /*    }), */
                /* ] */
            ],
        ])->withViewData([
            'JSONLD_Schemas' => [$this->getBreadcrumbSchema($breadcrumbs)],
        ]);
    }

    public function category($lang, Category $category)
    {
        $this->getBanners();
        $this->getTenantLinks();

        // Share other language URL for locale switching
        Inertia::share('otherLangURL', route('category', [
            'category' => $category->alias,
            'lang' => $this->getOtherLang(),
            'subdomain' => $this->subdomain,
        ]));

        $category->load(['pages' => function ($query): void {
            $query->select(['id', 'title', 'permalink', 'lang', 'category_id', 'tenant_id'])
                ->where('is_active', true);
        }])->load('pages.tenant:id,alias');

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: $category->name,
            description: $category->description,
        );

        return Inertia::render('Public/CategoryPage', [
            'category' => $category->only('id', 'name', 'description', 'pages'),
        ]);
    }

    public function summerCamps($lang, $year = null)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('pirmakursiuStovyklos');

        if ($year == null) {
            $year = intval(date('Y'));
        } else {
            $year = intval($year);
        }

        // TODO: add alias in global settings instead
        // The category is a grouping key here, not a publication gate: trashing the
        // "freshmen-camps" category must not silently empty this public archive.
        $events = Calendar::query()->whereHas('category', function (Builder $query): void {
            /** @var Builder<Category> $query */
            $query->withTrashed()->where('alias', '=', 'freshmen-camps');
        })->with('tenant:id,alias,fullname')->whereYear('date', $year)
            ->with(['media']);

        // Filter by locale - only show international events for English users
        if (app()->getLocale() === 'en') {
            $events->where('is_international', true);
        }

        // Grouped by faculty on the page, chronological within each faculty — a faculty
        // may run more than one camp.
        $events = $events->get()->sortBy([
            ['tenant.alias', 'asc'],
            ['date', 'asc'],
        ])->values();

        if ($events->isEmpty() && $year != intval(date('Y'))) {
            return redirect()->route('pirmakursiuStovyklos', ['lang' => app()->getLocale(), 'year' => null]);
        }

        $yearsWhenEventsExist = Calendar::query()->whereHas('category', function (Builder $query): void {
            /** @var Builder<Category> $query */
            $query->withTrashed()->where('alias', '=', 'freshmen-camps');
        });

        // Filter by locale for years when events exist
        if (app()->getLocale() === 'en') {
            $yearsWhenEventsExist->where('is_international', true);
        }

        // Grouped in PHP rather than with a `YEAR()` expression, which is MySQL-specific.
        $yearsWhenEventsExist = $yearsWhenEventsExist
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->unique()
            ->values();

        // Global content - use main vusa tenant (null defaults to current tenant).
        // This route only exists on the www domain group, so the derived " - VU SA"
        // suffix matches what was previously hardcoded here.
        $this->applyPageHead(
            contentTenant: null,
            title: $year == intval(date('Y')) ? 'Pirmakursių stovyklos' : $year.' m. pirmakursių stovyklos',
            description: 'Universiteto tvarka niekada su ja nesusidūrusiam žmogui gali pasirodyti labai sudėtinga ir būtent dėl to jau prieš septyniolika metų Vilniaus universiteto Studentų atstovybė (VU SA) surengė pirmąją pirmakursių stovyklą.',
            image: config('app.url').'/images/photos/stovykla.jpg',
        );

        return Inertia::render('Public/SummerCamps',
            [
                // `location` is shown on the camp cards; `description` stays hidden because
                // the cards never render it and it is heavy rich text.
                'events' => $events->makeHidden(['description', 'category', 'user_id'])->values()->all(),
                'year' => $year,
                'yearsWhenEventsExist' => $yearsWhenEventsExist,
            ]);
    }

    public function individualStudies()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('individualStudies');

        // Global content - use null for current tenant. This route only exists on the www
        // domain group, so the derived " - VU SA" suffix matches what was hardcoded here.
        $this->applyPageHead(
            contentTenant: null,
            title: __('Individualios studijos'),
            description: app()->getLocale() === 'lt' ? 'Nuo 2023 m. Vilniaus universitete kiekvienas naujai įstojęs (-usi) bakalauro ar vientisųjų studijų programos studentas (-ė) turi galimybę dėlioti savo studijas pagal asmeninius interesus, pasinaudodas (-a) individualių studijų galimybe.' : 'Since 2023 m. every newly
            enrolled bachelor\'s or integrated study program student at Vilnius University has the opportunity to arrange their studies according to personal interests, using the possibility of individual studies.',
        );

        return Inertia::render('Public/IndividualStudies');
    }

    // dynamically grabs list of pkp
    public function pkp()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('pkp');

        $institutions = (new InstitutionService)->getInstitutionsByTypeSlug('pkp')->where('is_active', true);

        // Global content - use null for current tenant. This route only exists on the www
        // domain group, so the derived " - VU SA" suffix matches what was hardcoded here.
        $this->applyPageHead(
            contentTenant: null,
            title: __('Studentiškos iniciatyvos'),
            description: 'VU SA studentiškos iniciatyvos – plati erdvė Vilniaus universiteto studentų(-čių) idėjoms, kūrybiškumui ir savirealizacijai.'
        );

        return Inertia::render('Public/PKP', [
            'institutions' => $institutions->map(function ($institution) {
                /** @var Institution $institution */
                return [
                    ...$institution->toArray(),
                    'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                ];
            }),
        ]);
    }

    public function curatorRegistrations()
    {
        $this->getBanners();
        $this->getTenantLinks();

        // Share other language URL for locale switching
        $this->shareOtherLangURL('curatorRegistrations');

        // Global content - use null for current tenant
        $this->applyPageHead(
            contentTenant: null,
            title: app()->getLocale() === 'lt' ? 'Registracija į kuratorių programą' : 'Registration to mentor program',
            description: 'Kuratoriai - tai studentai, kurie savo laisvalaikiu padeda naujiems studentams prisitaikyti prie universiteto aplinkos, dalinasi patirtimi ir patarimais, skatina aktyvų studentų gyvenimą.'
        );

        $forms = [
            'chgf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUNjNTNU9ESE4wV0s4RTA2QUtIMllVN0RSNC4u',
            'evaf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUNDI2Q0xKWktSSVFVR1RUOENEUk9QUlRFVy4u',
            'ff' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUOEZBSDk4NFZWRUJDMjJBOVU1TEtHWFJDNy4u',
            'filf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVURVY3TlNWQ0VHTjcwU1BVMEI1NjA5N04xTS4u',
            'fsf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUMlRLOFozV1RNWEZXMDdJODUzSDhQTllKWS4u',
            'gmc' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUQk1TVTlTM0k0MlpPTkZUSjczWU9HMDNTUi4u',
            'if' => 'https://forms.cloud.microsoft/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1utank1gTtJOjW_KfzCkXc1UN0NLNjM2SzRXQkwzT0NUTVQ1NjFKMjFIOS4u',
            'kf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVURU05RU1FUU5LODVCRjRaMzI3VkRRNFY3Sy4u',
            'knf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUN1A4RFhKWDVXVjUwNDdZUkZEUjgzNzkzRi4u',
            'mf' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUMllPRDFVSkZWOUQ4SVJRSjhJTkRSVUJYVy4u',
            'mif' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUOE03NUhXMFpON1RBT0hCODZLUFpFOUdDWS4u',
            'sa' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUMTlRQ1lIWUVMR0xNN0lSUzYzUzkwNDUzWi4u',
            'tf' => 'mailto:integracija@tf.vusa.lt',
            'tspmi' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUNUc2UUtSREk4OExZT0VEMlkwRExKUUw5Ry4u',
            'vm' => 'https://forms.office.com/Pages/ResponsePage.aspx?id=XVfIeiHvL0yhJSx6Ldsk1qutBUuXL4FKrkfpwBeQGxVUNEdYVzJTSURWRkdXMVZFMlhFUVkyREk0UC4u',
        ];

        $english_tenant_names = [
            'chgf' => 'Faculty of Chemistry and Geosciences',
            'evaf' => 'Faculty of Economics and Business Administration',
            'ff' => 'Faculty of Physics',
            'filf' => 'Faculty of Philology',
            'fsf' => 'Faculty of Philosophy',
            'gmc' => 'Life Sciences Center',
            'if' => 'Faculty of History',
            'kf' => 'Faculty of Communication',
            'knf' => 'Kaunas Faculty',
            'mf' => 'Faculty of Medicine',
            'mif' => 'Faculty of Mathematics and Informatics',
            'sa' => 'Šiauliai Academy',
            'tf' => 'Faculty of Law',
            'tspmi' => 'Institute of International Relations and Political Science',
            'vm' => 'Business School',
        ];

        $tenants = Tenant::query()->where('type', TenantType::Padalinys)->with('primary_institution')->orderBy('fullname')
            ->get(['id', 'primary_institution_id', 'alias', 'fullname']);

        Inertia::share('otherLangURL', LocalizedRouteSlugs::route('curatorRegistrations', [], $this->getOtherLang()));

        return Inertia::render('Public/CuratorRegistrations', [
            'forms' => $forms,
            'tenants' => $tenants,
            'englishTenantNames' => $english_tenant_names,
        ]);
    }

    public function calendarEvent(Calendar $calendar, LocationGeocoder $geocoder)
    {
        return $this->calendarEventMain('lt', $calendar, $geocoder);
    }

    public function calendarMain($lang, string $year, string $month, string $day, string $slug, LocationGeocoder $geocoder)
    {

        // Find the calendar event by date and slug
        $calendarEvents = Calendar::query()->whereDate('date', $year.'-'.$month.'-'.$day)->get();

        $returnableEvent = null;

        // Sluggify each event title and compare with the slug from the URL
        $calendarEvents->each(function ($event) use ($slug, &$returnableEvent): void {
            $sluggifiedTitle = Str::slug($event->title);
            if ($sluggifiedTitle === $slug) {
                $returnableEvent = $event;
            }
        });

        if ($returnableEvent === null) {
            abort(404);
        }

        return $this->calendarEventMain($lang, $returnableEvent, $geocoder);
    }

    public function calendarEventList()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('calendar.list');

        $now = Carbon::now();
        $perPage = 20; // Number of events per page
        $tab = request()->input('tab', 'upcoming'); // Default tab is upcoming

        // Create base query with common filters
        $query = Calendar::query()
            ->with(['category', 'tenant:id,alias,shortname,fullname'])
            ->where('is_draft', false);

        // Filter by locale
        if (app()->getLocale() === 'en') {
            $query->where('is_international', true);
        }

        // Apply common filters from request parameters
        $this->applyCalendarFilters($query);

        // Apply tab-specific filters and ordering
        if ($tab === 'past') {
            $query->where('date', '<', $now->format('Y-m-d'))
                ->orderBy('date', 'desc');
        } else {
            // Default to upcoming
            $query->where('date', '>=', $now->format('Y-m-d'))
                ->orderBy('date', 'asc');
        }

        // Execute pagination
        $events = $query->paginate($perPage)
            ->through(fn ($event) => [
                ...$event->toArray(),
                'googleLink' => $event->googleLink(),
                'images' => $event->getMedia('images'),
            ]);

        // Get all available filter options based on tab
        $filterOptions = $this->getCalendarFilterOptions($tab);

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Visų renginių sąrašas'),
            description: __('Vilniaus universiteto Studentų atstovybės ir bendruomenės renginių sąrašas.'),
        );

        // Share pagination SEO metadata for rel=next/prev links
        $this->sharePaginationSeoMeta($events, $this->tenant);

        return Inertia::render('Public/CalendarEventList', [
            'events' => $events,
            'activeTab' => $tab,
            'allCategories' => $filterOptions['categories'],
            'allTenants' => $filterOptions['tenants'],
        ]);
    }

    /**
     * Get filter options for calendar events based on tab
     *
     * For 'upcoming' tab: Only show categories and tenants that have upcoming events
     * For 'past' tab: Show all categories and tenants
     */
    private function getCalendarFilterOptions(string $tab): array
    {
        $now = Carbon::now();
        $categories = [];
        $tenants = [];

        if ($tab === 'past') {
            // For past events, get ALL categories and tenants regardless of current filter
            $categories = Category::query()
                ->whereHas('calendars', function ($query): void {
                    // Only get categories that have calendar events
                    $query->where('is_draft', false);

                    // Apply language filter
                    if (app()->getLocale() === 'en') {
                        $query->where('is_international', true);
                    }
                })
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();

            $tenants = Tenant::query()
                ->whereHas('calendar', function ($query): void {
                    // Only get tenants that have calendar events
                    $query->where('is_draft', false);

                    // Apply language filter
                    if (app()->getLocale() === 'en') {
                        $query->where('is_international', true);
                    }
                })
                ->select('id', 'shortname')
                ->orderBy('shortname')
                ->get()
                ->toArray();
        } else {
            // For upcoming events, only get categories and tenants that have upcoming events
            $categories = Category::query()
                ->whereHas('calendars', function ($query) use ($now): void {
                    $query->where('is_draft', false)
                        ->where('date', '>=', $now->format('Y-m-d'));

                    // Apply language filter
                    if (app()->getLocale() === 'en') {
                        $query->where('is_international', true);
                    }
                })
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();

            $tenants = Tenant::query()
                ->whereHas('calendar', function ($query) use ($now): void {
                    $query->where('is_draft', false)
                        ->where('date', '>=', $now->format('Y-m-d'));

                    // Apply language filter
                    if (app()->getLocale() === 'en') {
                        $query->where('is_international', true);
                    }
                })
                ->select('id', 'shortname')
                ->orderBy('shortname')
                ->get()
                ->toArray();
        }

        return [
            'categories' => $categories,
            'tenants' => $tenants,
        ];
    }

    /**
     * Apply filters to calendar query
     */
    private function applyCalendarFilters($query)
    {
        // Filter by category if provided
        if (request()->has('category') && request()->category) {
            $query->where('category_id', request()->category);
        }

        // Filter by tenant if provided
        if (request()->has('tenant') && request()->tenant) {
            $query->where('tenant_id', request()->tenant);
        }

        // Filter by search term if provided
        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    public function calendarEventRedirect($lang, Calendar $calendar)
    {
        // Get a non-empty title with fallback to other locales
        $titleData = $this->getNonEmptyCalendarTitle($calendar);
        $title = $titleData['title'];
        $usedLocale = $titleData['locale'];

        // Generate slug from the non-empty title
        $slug = Str::slug($title);

        // Fallback to ID-based slug if still empty (shouldn't happen, but defensive)
        if (empty($slug)) {
            $slug = 'event-'.$calendar->id;
        }

        return redirect(route('calendar.event.2', [
            'year' => $calendar->date->format('Y'),
            'month' => $calendar->date->format('m'),
            'day' => $calendar->date->format('d'),
            'slug' => $slug,
            'lang' => $usedLocale,
        ]), 301);
    }

    /**
     * Get a non-empty calendar title with fallback to other locales
     *
     * @return array{title: string, locale: string}
     */
    private function getNonEmptyCalendarTitle(Calendar $calendar): array
    {
        $currentLocale = app()->getLocale();

        // Try current locale first
        $title = $calendar->getTranslation('title', $currentLocale);
        if (! empty(trim($title))) {
            return ['title' => $title, 'locale' => $currentLocale];
        }

        // Fallback priority: lt -> en -> any available
        $fallbackLocales = ['lt', 'en'];

        foreach ($fallbackLocales as $locale) {
            if ($locale === $currentLocale) {
                continue; // Already tried
            }
            $title = $calendar->getTranslation('title', $locale);
            if (! empty(trim($title))) {
                return ['title' => $title, 'locale' => $locale];
            }
        }

        // Try any available translation
        $translations = $calendar->getTranslations('title');
        foreach ($translations as $locale => $translation) {
            if (! empty(trim($translation))) {
                return ['title' => $translation, 'locale' => $locale];
            }
        }

        // Last resort: use calendar ID
        return ['title' => 'Event '.$calendar->id, 'locale' => $currentLocale];
    }

    /**
     * The meeting this event announces, shaped for the page: agenda, documents and a link back
     * to the meeting record. Null for an ordinary event, or when the event is still a draft.
     *
     * @return array<string, mixed>|null
     */
    private function meetingBehind(Calendar $calendar): ?array
    {
        if ($calendar->is_draft || $calendar->meeting_id === null) {
            return null;
        }

        $meeting = $calendar->meeting;

        if ($meeting === null || $meeting->trashed()) {
            return null;
        }

        $meeting->load([
            'agendaItems' => fn ($query) => $query->orderBy('order')->orderBy('start_time'),
            'agendaItems.mainVote',
            'institutions.types',
        ]);

        $institution = $meeting->institutions->first();

        return [
            'id' => $meeting->id,
            'start_time' => $meeting->start_time,
            'agenda_items' => $meeting->agendaItems,
            'requires_student_perspective' => $meeting->requiresStudentPerspective(),
            'documents' => GetPublicMeetingDocuments::execute($meeting),
            'institution' => $institution?->only(['id', 'name', 'alias']),
            // The event page shows the agenda regardless, but a link to the meeting page/search
            // entry must not point somewhere that 404s — see Meeting::isPubliclyVisible().
            'is_publicly_visible' => $meeting->isPubliclyVisible(),
        ];
    }

    /**
     * The nearest published announcements before/after this one, for the same institution —
     * calendar event to calendar event, not meeting to meeting, so the links stay valid
     * regardless of MeetingSettings.
     *
     * @param  array<string, mixed>  $meeting  The array shaped by meetingBehind().
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function siblingMeetingEvents(Calendar $calendar, array $meeting): array
    {
        $institutionId = $meeting['institution']['id'] ?? null;

        if ($institutionId === null) {
            return [null, null];
        }

        $siblingsFor = fn (string $direction) => Calendar::query()
            ->where('is_draft', false)
            ->where('id', '!=', $calendar->id)
            ->whereHas('meeting.institutions', fn ($q) => $q->where('institutions.id', $institutionId))
            ->where('date', $direction === 'previous' ? '<' : '>', $calendar->date)
            ->orderBy('date', $direction === 'previous' ? 'desc' : 'asc')
            ->first(['id', 'title', 'date']);

        return [
            $siblingsFor('previous')?->only(['id', 'title', 'date']),
            $siblingsFor('next')?->only(['id', 'title', 'date']),
        ];
    }

    /**
     * The events offered alongside this one: the soonest still to come first, topped up
     * with the most recent past ones when little is coming.
     *
     * Not `getEventsForCalendar()` — that sorts the whole calendar newest-first, so
     * reading from its top surfaced whatever is furthest in the future rather than what
     * is about to happen.
     *
     * @return array<int, array<string, mixed>>
     */
    private function otherEventsAround(Calendar $calendar, int $limit = 4): array
    {
        $now = Carbon::now();

        $base = fn () => Calendar::query()
            ->with(['category', 'media', 'tenant:id,alias,shortname,fullname'])
            ->forLocale(app()->getLocale())
            ->where('is_draft', false)
            ->whereKeyNot($calendar->id);

        // An event that has started but not ended is still ahead of the reader, so it
        // ranks with the upcoming ones rather than the archive.
        $upcoming = $base()
            ->where(fn (Builder $query) => $query->where('date', '>=', $now)->orWhere('end_date', '>=', $now))
            ->orderBy('date')
            ->take($limit)
            ->get();

        $events = $upcoming->count() >= $limit
            ? $upcoming
            : $upcoming->concat(
                $base()
                    ->where('date', '<', $now)
                    ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '<', $now))
                    ->orderByDesc('date')
                    ->take($limit - $upcoming->count())
                    ->get()
            );

        return $events->map(fn (Calendar $event) => [
            ...$event->toArray(),
            'images' => $event->getMedia('images'),
            'googleLink' => $event->googleLink(),
        ])->all();
    }

    public function calendarEventMain($lang, Calendar $calendar, LocationGeocoder $geocoder)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('calendar.event', calendarId: $calendar->id);

        $calendar->load(['tenant:id,alias,fullname,shortname', 'category']);

        $this->sharePublicEditLink($calendar);

        $meeting = $this->meetingBehind($calendar);
        [$previousMeetingEvent, $nextMeetingEvent] = $meeting !== null
            ? $this->siblingMeetingEvents($calendar, $meeting)
            : [null, null];

        // Use the calendar event's tenant for proper canonical URL
        $this->applyPageHead(
            contentTenant: $calendar->tenant,
            title: $calendar->title,
            // Replace " with empty string, because it breaks JSON-LD
            description: app()->getLocale() === 'lt' ? Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: '') : Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: ''),
            image: $calendar->getFirstMediaUrl('images'),
            publishedTime: $calendar->created_at,
            modifiedTime: $calendar->updated_at,
        );

        $relatedEvents = $this->otherEventsAround($calendar);

        // Generate breadcrumb schema
        $locale = app()->getLocale();
        $breadcrumbs = [
            [
                'name' => $locale === 'lt' ? 'Pradžia' : 'Home',
                'url' => route('home', ['subdomain' => $this->subdomain, 'lang' => $locale]),
            ],
            [
                'name' => $locale === 'lt' ? 'Renginiai' : 'Events',
                'url' => route('calendar.list', ['subdomain' => $this->subdomain, 'lang' => $locale]),
            ],
            [
                'name' => $calendar->title,
                'url' => route('calendar.event.2', [
                    'subdomain' => $this->subdomain,
                    'lang' => $locale,
                    'year' => $calendar->date->format('Y'),
                    'month' => $calendar->date->format('m'),
                    'day' => $calendar->date->format('d'),
                    'slug' => Str::slug($calendar->title),
                ]),
            ],
        ];

        return Inertia::render('Public/CalendarEvent', [
            'event' => [
                ...$calendar->toArray(),
                'images' => $calendar->getMedia('images'),
            ],
            'calendar' => $relatedEvents,
            'googleLink' => $calendar->googleLink(),
            'eventLocation' => $calendar->is_remote ? null : $geocoder->coordinates($calendar->location),
            'meeting' => $meeting,
            'previousMeetingEvent' => $previousMeetingEvent,
            'nextMeetingEvent' => $nextMeetingEvent,
        ])
            ->withViewData(
                [
                    'JSONLD_Schemas' => [
                        $this->getBreadcrumbSchema($breadcrumbs),
                        $calendar->toEventSchema(),
                    ],
                ]
            );
    }

    public function registrationPage($lang, $registrationString, string $registrationForm)
    {

        $this->getBanners();
        $this->getTenantLinks();

        $form = Form::query()->whereJsonContains('path->'.$lang, $registrationForm)->with(['formFields' => function ($query): void {
            $query->orderBy('order');
        }])->firstOrFail();

        // Submissions are rejected before publish_time, so don't show a form that cannot be submitted.
        if ($form->publish_time?->isFuture()) {
            abort(404);
        }

        $otherLocale = app()->getLocale() === 'lt' ? 'en' : 'lt';

        Inertia::share('otherLangURL', LocalizedRouteSlugs::route('registrationPage', [
            'registrationForm' => $form->getTranslation('path', $otherLocale),
        ], $otherLocale));

        // Global content - use null for current tenant
        $this->applyPageHead(
            contentTenant: null,
            title: $form->name,
        );

        // Check if this is the student rep registration form
        $formSettings = app(FormSettings::class);
        $isStudentRepForm = $form->id === $formSettings->student_rep_registration_form_id;

        // Get pre-selected institution from query param (for autofill)
        $preselectedInstitutionId = request()->query('institution');

        return Inertia::render('Public/RegistrationPage', [
            'form' => [
                ...$form->toArray(),
                'form_fields' => $form->formFields->map(function ($field) use ($isStudentRepForm, $formSettings, $preselectedInstitutionId) {
                    $options = $field->options;

                    if ($field->use_model_options) {
                        // Special handling for Institution model on student rep form
                        if ($isStudentRepForm && $field->options_model === Institution::class) {
                            $options = $this->getInstitutionsWithoutActiveReps($formSettings, $preselectedInstitutionId)->map(fn ($model) => [
                                'value' => $model->getKey(),
                                'label' => $model->getAttribute($field->options_model_field),
                            ]);
                        } else {
                            $options = $field->options_model::all()->map(fn (Model $model) => [
                                'value' => $model->getKey(),
                                'label' => $model->getAttribute($field->options_model_field),
                            ]);
                        }
                    }

                    return [
                        ...$field->toArray(),
                        'options' => $options,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get institutions that have duties with no active members (for student rep registration).
     * Always includes the preselected institution if provided.
     */
    protected function getInstitutionsWithoutActiveReps(FormSettings $formSettings, ?string $preselectedInstitutionId = null): Collection
    {
        $allowedTypeIds = $formSettings->getStudentRepInstitutionTypeIds();

        $query = Institution::query()
            ->where(function ($q) use ($preselectedInstitutionId): void {
                // Include institutions that do not have duties with active users.
                // Here `duties` is the Institution -> Duty relationship and `current_users` is a nested
                // relationship/scope on Duty that returns only the members currently active in that duty.
                $q->whereDoesntHave('duties.current_users');

                // Always include the preselected institution
                if ($preselectedInstitutionId) {
                    $q->orWhere('id', $preselectedInstitutionId);
                }
            });

        // Filter by allowed institution types if configured
        if ($allowedTypeIds->isNotEmpty()) {
            $query->whereHas('types', function ($q) use ($allowedTypeIds): void {
                $q->whereIn('types.id', $allowedTypeIds);
            });
        }

        return $query->get();
    }

    /**
     * Calculate and cache membership statistics
     */
    protected function getMembershipStats(): array
    {
        $cacheKey = 'membership_stats';

        return Cache::remember($cacheKey, 3600, function () { // 60 minutes TTL
            // Get the student representative type and its descendants
            $representativeType = Type::query()->where('slug', '=', 'studentu-atstovu-organas')->first();

            if (! $representativeType) {
                // Fallback if type doesn't exist
                return [
                    'representative_bodies' => 0,
                    'student_representatives' => 0,
                    'cached_at' => now(),
                ];
            }

            $representativeTypes = $representativeType->getDescendantsAndSelf();

            // Calculate number of representative bodies (institutions with student representative types)
            // Exclude 'pkp' type tenants as they're student initiatives, not formal representation
            // Also exclude institutions that don't have any active users in their duties
            $representativeBodies = Institution::query()
                ->whereHas('types', function ($query) use ($representativeTypes): void {
                    $query->whereIn('id', $representativeTypes->pluck('id'));
                })
                ->whereHas('tenant', function ($query): void {
                    $query->whereIn('type', TenantType::representationalValues());
                })
                ->whereHas('duties.current_users') // Only count institutions that have active users
                ->where('is_active', true)
                ->count();

            // Calculate unique student representatives
            // Get all institutions with representative types and their current users
            $institutions = Institution::query()
                ->whereHas('types', function ($query) use ($representativeTypes): void {
                    $query->whereIn('id', $representativeTypes->pluck('id'));
                })
                ->whereHas('tenant', function ($query): void {
                    $query->whereIn('type', TenantType::representationalValues());
                })
                ->whereHas('duties.current_users') // Only get institutions that have active users
                ->where('is_active', true)
                ->with(['duties.current_users'])
                ->get();

            // Collect all unique user IDs from all duties in these institutions
            $uniqueUserIds = collect();

            foreach ($institutions as $institution) {
                foreach ($institution->duties as $duty) {
                    $uniqueUserIds = $uniqueUserIds->merge($duty->current_users->pluck('id'));
                }
            }

            $studentRepresentativeCount = $uniqueUserIds->unique()->count();

            return [
                'representative_bodies' => $representativeBodies,
                'student_representatives' => $studentRepresentativeCount,
                'cached_at' => now(),
            ];
        });
    }

    public function membership()
    {
        $this->getBanners();
        $this->getTenantLinks();

        // Share other language URL for locale switching
        $this->shareOtherLangURL('joinUs');

        // Get membership statistics
        $membershipStats = $this->getMembershipStats();

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Tapk VU SA nariu'),
            description: __('Prisijunk prie VU SA bendruomenės!')
        );

        return Inertia::render('Public/MembershipPage', [
            'membershipStats' => $membershipStats,
        ]);
    }
}
