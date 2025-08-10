<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ResourceServices\InstitutionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                        ->orderBy('date', 'desc')->take(100)->get()->map(function ($event) {
                            return [
                                ...$event->toArray(),
                                'images' => $event->getMedia('images'),
                                'googleLink' => $event->googleLink(),
                            ];
                        });
                } else {
                    return Calendar::query()->with(['category', 'media'])->where('is_draft', false)
                        ->orderBy('date', 'desc')->take(100)->get()->map(function ($event) {
                            return [
                                ...$event->toArray(),
                                'images' => $event->getMedia('images'),
                                'googleLink' => $event->googleLink(),
                            ];
                        });
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
                return $this->tenant->content ??
                    Tenant::query()->where('type', 'pagrindinis')->first()->content;
            });

        $seo = $this->shareAndReturnSEOObject(title: __('Pagrindinis puslapis').' - '.$this->tenant->shortname);

        return Inertia::render('Public/HomePage', [
            'content' => $content,
        ])->withViewData([
            'SEOData' => $seo,
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

        Inertia::share('otherLangURL', $other_lang_page ? route(
            'page',
            [
                'subdomain' => $this->subdomain,
                'lang' => $other_lang_page->lang,
                'permalink' => $other_lang_page->permalink,
            ]
        ) : null);

        // Get description for SEO from first tiptap element
        $seo = $this->shareAndReturnSEOObject(
            title: $page->title.' - '.$this->tenant->shortname,
            description: ContentHelper::getDescriptionForSeo($page),
        );

        return Inertia::render('Public/ContentPage', [
            'navigationItemId' => $navigation_item?->id,
            'page' => [
                ...$page->only('id', 'title', 'lang', 'category', 'tenant', 'permalink', 'other_lang_id'),
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
            'SEOData' => $seo,
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

        $category->load(['pages' => function ($query) {
            $query->select(['id', 'title', 'permalink', 'lang', 'category_id', 'tenant_id'])
                ->where('is_active', true);
        }])->load('pages.tenant:id,alias');

        $seo = $this->shareAndReturnSEOObject(
            title: $category->name.' - '.$this->tenant->shortname,
            description: $category->description,
        );

        return Inertia::render('Public/CategoryPage', [
            'category' => $category->only('id', 'name', 'description', 'pages'),
        ])->withViewData([
            'SEOData' => $seo,
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
        $events = Calendar::query()->whereHas('category', function (Builder $query) {
            $query->where('alias', '=', 'freshmen-camps');
        })->with('tenant:id,alias,fullname')->whereYear('date', $year)
            ->with(['media'])->get()->sortBy('tenant.alias')->values();

        if ($events->isEmpty() && $year != intval(date('Y'))) {
            return redirect()->route('pirmakursiuStovyklos', ['lang' => app()->getLocale(), 'year' => null]);
        }

        $yearsWhenEventsExist = Calendar::query()->whereHas('category', function (Builder $query) {
            $query->where('alias', '=', 'freshmen-camps');
        })->selectRaw('YEAR(date) as year')->distinct()->get()->pluck('year');

        $seo = $this->shareAndReturnSEOObject(
            title: $year == intval(date('Y')) ? 'Pirmakursių stovyklos - VU SA' : $year.' m. pirmakursių stovyklos - VU SA',
            description: 'Universiteto tvarka niekada su ja nesusidūrusiam žmogui gali pasirodyti labai sudėtinga ir būtent dėl to jau prieš septyniolika metų Vilniaus universiteto Studentų atstovybė (VU SA) surengė pirmąją pirmakursių stovyklą.',
            image: config('app.url').'/images/photos/stovykla.jpg',
        );

        return Inertia::render('Public/SummerCamps',
            [
                'events' => $events->makeHidden(['description', 'location', 'category', 'url', 'user_id'])->values()->all(),
                'year' => $year,
                'yearsWhenEventsExist' => $yearsWhenEventsExist,
            ])->withViewData([
                'SEOData' => $seo,
            ]);
    }

    public function individualStudies()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('individualStudies');

        $seo = $this->shareAndReturnSEOObject(
            title: __('Individualios studijos').' - VU SA',
            description: app()->getLocale() === 'lt' ? 'Nuo 2023 m. Vilniaus universitete kiekvienas naujai įstojęs (-usi) bakalauro ar vientisųjų studijų programos studentas (-ė) turi galimybę dėlioti savo studijas pagal asmeninius interesus, pasinaudodas (-a) individualių studijų galimybe.' : 'Since 2023 m. every newly 
            enrolled bachelor\'s or integrated study program student at Vilnius University has the opportunity to arrange their studies according to personal interests, using the possibility of individual studies.',
        );

        return Inertia::render('Public/IndividualStudies')->withViewData([
            'SEOData' => $seo,
        ]);
    }

    // dynamically grabs list of pkp
    public function pkp()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('pkp');

        $institutions = (new InstitutionService)->getInstitutionsByTypeSlug('pkp')->where('is_active', true);

        $seo = $this->shareAndReturnSEOObject(
            title: __('Studentiškos iniciatyvos').' - VU SA',
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
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }

    public function curatorRegistrations()
    {
        $this->getBanners();
        $this->getTenantLinks();

        // Share other language URL for locale switching
        $this->shareOtherLangURL('curatorRegistrations');

        $seo = $this->shareAndReturnSEOObject(
            title: app()->getLocale() === 'lt' ? 'Registracija į kuratorių programą' : 'Registration to mentor program',
            description: 'Kuratoriai - tai studentai, kurie savo laisvalaikiu padeda naujiems studentams prisitaikyti prie universiteto aplinkos, dalinasi patirtimi ir patarimais, skatina aktyvų studentų gyvenimą.'
        );

        $forms = [
            'chgf' => 'https://forms.office.com/e/m5efiVLTnb',
            'evaf' => 'https://forms.office.com/e/iAhN6ScQ3H',
            'ff' => 'https://forms.office.com/e/kNFgdk7ZDF',
            'filf' => 'https://forms.office.com/e/LyYYN3btqN',
            'fsf' => 'https://forms.office.com/e/xKMfZrgAVh',
            'gmc' => 'https://forms.office.com/e/04PN2ajihu',
            'if' => 'https://forms.office.com/e/0pn0SZ02b0',
            'kf' => 'https://forms.office.com/e/UdnpVRLdPk',
            'knf' => 'https://forms.office.com/e/PAKadETKhQ',
            'mf' => 'https://forms.office.com/e/CjxQ590Nsh',
            'mif' => 'https://forms.office.com/e/dh0UbRhjEn',
            'sa' => 'https://forms.office.com/e/pz1S1KkFfF',
            'tf' => 'https://forms.office.com/e/znCEWZrju1',
            'tspmi' => 'https://forms.office.com/e/BNHUFeS27g',
            'vm' => 'https://forms.office.com/e/Uf428VaLyv',
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

        $tenants = Tenant::query()->where('type', 'padalinys')->with('primary_institution')->orderBy('fullname')
            ->get(['id', 'primary_institution_id', 'alias', 'fullname']);

        Inertia::share('otherLangURL',
            route('curatorRegistrations',
                [
                    'lang' => $this->getOtherLang(),
                    'registrationString' => app()->getLocale() === 'lt' ? 'registration-to-mentor-program' : 'registracija-i-kuratoriu-programa',
                ])
        );

        return Inertia::render('Public/CuratorRegistrations', [
            'forms' => $forms,
            'tenants' => $tenants,
            'englishTenantNames' => $english_tenant_names,
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }

    public function calendarEvent(Calendar $calendar)
    {
        return $this->calendarEventMain('lt', $calendar);
    }

    public function calendarMain($lang, string $year, string $month, string $day, string $slug)
    {

        // Find the calendar event by date and slug
        $calendarEvents = Calendar::query()->whereDate('date', $year.'-'.$month.'-'.$day)->get();

        $returnableEvent = null;

        // Sluggify each event title and compare with the slug from the URL
        $calendarEvents->each(function ($event) use ($slug, &$returnableEvent) {
            $sluggifiedTitle = Str::slug($event->title);
            if ($sluggifiedTitle === $slug) {
                $returnableEvent = $event;
            }
        });

        if ($returnableEvent === null) {
            abort(404);
        }

        return $this->calendarEventMain($lang, $returnableEvent);
    }

    public function calendarEventList()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('calendar.list');

        // Disable page transition on filter changes
        if (request()->has(['search']) || request()->has(['category']) ||
            request()->has(['tenant']) || request()->has(['tab']) || request()->has(['page'])) {
            Inertia::share('disablePageTransition', true);
        }

        $now = Carbon::now();
        $perPage = 20; // Number of events per page
        $tab = request()->get('tab', 'upcoming'); // Default tab is upcoming

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
            ->through(function ($event) {
                return [
                    ...$event->toArray(),
                    'googleLink' => $event->googleLink(),
                    'images' => $event->getMedia('images'),
                ];
            });

        // Get all available filter options based on tab
        $filterOptions = $this->getCalendarFilterOptions($tab);

        $seo = $this->shareAndReturnSEOObject(
            title: __('Visų renginių sąrašas').' - '.$this->tenant->shortname,
            description: __('Vilniaus universiteto Studentų atstovybės ir bendruomenės renginių sąrašas.'),
        );

        return Inertia::render('Public/CalendarEventList', [
            'events' => $events,
            'activeTab' => $tab,
            'allCategories' => $filterOptions['categories'],
            'allTenants' => $filterOptions['tenants'],
        ])->withViewData([
            'SEOData' => $seo,
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
            $categories = \App\Models\Category::query()
                ->whereHas('calendars', function ($query) {
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

            $tenants = \App\Models\Tenant::query()
                ->whereHas('calendar', function ($query) {
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
            $categories = \App\Models\Category::query()
                ->whereHas('calendars', function ($query) use ($now) {
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

            $tenants = \App\Models\Tenant::query()
                ->whereHas('calendar', function ($query) use ($now) {
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
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    public function calendarEventRedirect($lang, Calendar $calendar)
    {
        return redirect(route('calendar.event.2', [
            'year' => $calendar->date->format('Y'),
            'month' => $calendar->date->format('m'),
            'day' => $calendar->date->format('d'),
            'slug' => Str::slug($calendar->title),
            'lang' => app()->getLocale(),
        ]), 301);
    }

    public function calendarEventMain($lang, Calendar $calendar)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('calendar.event', calendarId: $calendar->id);

        $calendar->load('tenant:id,alias,fullname,shortname');

        $seo = $this->shareAndReturnSEOObject(
            title: $calendar->title.' - '.$this->tenant->shortname,
            // Replace " with empty string, because it breaks JSON-LD
            description: app()->getLocale() === 'lt' ? Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: '') : Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: ''),
            image: $calendar->getFirstMediaUrl('images'),
            published_time: $calendar->created_at,
            modified_time: $calendar->updated_at,
        );

        // Get related events without caching
        $relatedEvents = $this->getEventsForCalendar();

        return Inertia::render('Public/CalendarEvent', [
            'event' => [
                ...$calendar->toArray(),
                'images' => $calendar->getMedia('images'),
            ],
            'calendar' => $relatedEvents,
            'googleLink' => $calendar->googleLink(),
        ])
            ->withViewData(
                [
                    'SEOData' => $seo,
                ]
            );
    }

    public function registrationPage($lang, $registrationString, string $registrationForm)
    {

        $this->getBanners();
        $this->getTenantLinks();

        $form = Form::query()->whereJsonContains('path->'.$lang, $registrationForm)->with(['formFields' => function ($query) {
            $query->orderBy('order');
        }])->firstOrFail();

        $otherLocale = app()->getLocale() === 'lt' ? 'en' : 'lt';

        Inertia::share('otherLangURL', route('registrationPage', ['lang' => $otherLocale, 'registrationString' => $otherLocale === 'lt' ? 'registracija' : 'registration', 'registrationForm' => $form->getTranslation('path', $otherLocale)]));

        $seo = $this->shareAndReturnSEOObject(
            title: $form->name.' - '.$this->tenant->shortname,
        );

        return Inertia::render('Public/RegistrationPage', [
            'form' => [
                ...$form->toArray(),
                'form_fields' => $form->formFields->map(function ($field) {
                    $options = $field->options;

                    if ($field->use_model_options) {
                        $options = $field->options_model::all()->map(function ($model) use ($field) {
                            return [
                                'value' => $model->id,
                                'label' => $model->{$field->options_model_field},
                            ];
                        });
                    }

                    return [
                        ...$field->toArray(),
                        'options' => $options,
                    ];
                }),
            ],
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }

    public function membership()
    {
        $this->getBanners();
        $this->getTenantLinks();

        // Share other language URL for locale switching
        $this->shareOtherLangURL('joinUs');

        $seo = $this->shareAndReturnSEOObject(
            title: __('Tapk VU SA nariu').' - '.$this->tenant->shortname,
            description: __('Prisijunk prie VU SA bendruomenės ir būk studentų teisių gynėjas!')
        );

        return Inertia::render('Public/MembershipPage')->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
