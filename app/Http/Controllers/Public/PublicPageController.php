<?php

namespace App\Http\Controllers\Public;

use App\Actions\GetPublicMeetingDocuments;
use App\Collections\NewsCollection;
use App\Enums\LocaleEnum;
use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Http\Requests\IndexPublicCalendarRequest;
use App\Models\Calendar;
use App\Models\Content;
use App\Models\EventType;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\LocationGeocoder;
use App\Services\PublicUrlService;
use App\Services\ResourceServices\InstitutionService;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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
                    return Calendar::query()->with(['eventType', 'media'])->where('is_international', true)->where('is_draft', false)
                        ->orderBy('date', 'desc')->take(100)->get()->map(fn ($event) => [
                            ...$event->toArray(),
                            'images' => $event->getMedia('images'),
                            'googleLink' => $event->googleLink(),
                            'public_url' => $event->publicUrl($locale),
                        ]);
                } else {
                    return Calendar::query()->with(['eventType', 'media'])->where('is_draft', false)
                        ->orderBy('date', 'desc')->take(100)->get()->map(fn ($event) => [
                            ...$event->toArray(),
                            'images' => $event->getMedia('images'),
                            'googleLink' => $event->googleLink(),
                            'public_url' => $event->publicUrl($locale),
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
            ->remember($cacheKey, 3600, fn () => $this->homepageContentForLocale($this->tenant, $locale)
                ?? $this->homepageContentForLocale(Tenant::main(), $locale));

        // Fetch news for homepage to enable LCP image preloading (eliminates API waterfall)
        $newsCacheKey = "homepage_news_{$this->tenant->id}_{$locale}";

        // Only authenticated users pay for edit-link resolution.
        if (Auth::check()) {
            $this->sharePublicEditLink($this->tenant);
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
            'tenantSwitchTarget' => 'same-page',
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

    private function homepageContentForLocale(?Tenant $tenant, string $locale): ?Content
    {
        if ($tenant === null) {
            return null;
        }

        $tenant->loadMissing('homepageContents.content.parts');
        $homepageContents = $tenant->homepageContents->keyBy('locale');

        foreach (array_unique([$locale, LocaleEnum::LT->value]) as $candidateLocale) {
            $content = $homepageContents->get($candidateLocale)?->content;

            if ($content?->parts->isNotEmpty()) {
                return $content;
            }
        }

        return null;
    }

    public function page(?PublicUrlService $publicUrls = null)
    {
        $publicUrls ??= app(PublicUrlService::class);
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

                $children = $page->children()
                    ->where('is_active', true)
                    ->get(['id', 'title', 'permalink', 'lang', 'tenant_id', 'meta_description'])
                    ->map(fn (Page $child) => [
                        'title' => $child->title,
                        'url' => $child->publicUrl(),
                        'meta_description' => $child->meta_description,
                    ])
                    ->values();

                return [
                    'page' => $page,
                    'navigation_item' => $navigation_item,
                    'other_lang_page' => $other_lang_page,
                    'ancestors' => $page->ancestors(),
                    'children' => $children,
                ];
            });

        if ($pageData === null) {
            $publicUrl = $publicUrls->resolve(request()->url());
            $destination = $publicUrl === null ? null : $publicUrls->destinationFor($publicUrl);

            if ($destination !== null) {
                return redirect($destination, 301);
            }

            abort(404);
        }

        $page = $pageData['page'];
        $navigation_item = $pageData['navigation_item'];
        $other_lang_page = $pageData['other_lang_page'];
        $ancestors = $pageData['ancestors'];
        $children = $pageData['children'];

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

        // Add the page's ancestor chain (root first), replacing the old category crumb —
        // page structure is now expressed as structure, not as a category.
        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = [
                'name' => $ancestor->title,
                'url' => route('page', [
                    'subdomain' => $this->subdomain,
                    'lang' => $locale,
                    'permalink' => $ancestor->permalink,
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
                ...$page->only('id', 'title', 'lang', 'tenant', 'permalink', 'other_lang_id', 'layout', 'show_table_of_contents', 'show_title', 'show_breadcrumbs', 'highlights', 'featured_image', 'meta_description', 'last_edited_at', 'updated_at'),
                'content' => $page->content,
                // Section listing for this page's direct children (`Page::children()`) —
                // the permalink stays flat, this is presentation only.
                'children' => $children,
                'ancestors' => array_map(fn ($ancestor) => [
                    'id' => $ancestor->id,
                    'title' => $ancestor->title,
                    'permalink' => $ancestor->permalink,
                    'url' => route('page', [
                        'subdomain' => $this->subdomain,
                        'lang' => $locale,
                        'permalink' => $ancestor->permalink,
                    ]),
                ], $ancestors),
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

    /**
     * `categories` was retired in favor of topics/event types/page hierarchy (taxonomy
     * redesign phase 4). The seven aliases that ever existed are a fixed, known set —
     * this is a permanent redirect table, not a lookup against live data.
     */
    public function categoryRedirect(): RedirectResponse
    {
        $alias = (string) request()->route('alias');
        $locale = (string) (request()->route('lang') ?: app()->getLocale());

        // Categories carried no tenant relation, so — matching
        // NavigationLinkApiController::resolveCategoryUrl()'s previous rationale — every
        // destination resolves against `www` regardless of which subdomain was requested.
        $destination = match ($alias) {
            'red', 'yellow', 'grey' => LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www'], $locale),
            'freshmen-camps' => LocalizedRouteSlugs::route('pirmakursiuStovyklos', [], $locale),
            'vu-sa-conferences' => LocalizedRouteSlugs::route('calendar.list', ['type' => 'konferencija'], $locale),
            'stipendijos' => LocalizedRouteSlugs::route('topic', ['tag' => 'finansine-parama-stipendijos'], $locale),
            'vu-sa-dokumentai' => LocalizedRouteSlugs::route('documents', [], $locale),
            default => null,
        };

        abort_if($destination === null, 404);

        return redirect()->away($destination, 301);
    }

    public function summerCamps(string $lang, string $summerCampsString, ?string $year = null)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('pirmakursiuStovyklos');

        if ($year == null) {
            $year = intval(date('Y'));
        } else {
            $year = intval($year);
        }

        // TODO: add slug in global settings instead
        // The event type is a grouping key here, not a publication gate: trashing the
        // "stovykla" event type must not silently empty this public archive.
        $events = Calendar::query()->whereHas('eventType', function (Builder $query): void {
            /** @var Builder<EventType> $query */
            $query->withTrashed()->where('slug', '=', 'stovykla');
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

        $yearsWhenEventsExist = Calendar::query()->whereHas('eventType', function (Builder $query): void {
            /** @var Builder<EventType> $query */
            $query->withTrashed()->where('slug', '=', 'stovykla');
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
                'events' => $events->makeHidden(['description', 'user_id'])
                    ->map(fn (Calendar $event) => [
                        ...$event->toArray(),
                        'public_url' => $event->publicUrl(app()->getLocale()),
                    ])->values()->all(),
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

    // PKP is now a standard ContentPage using the institution-list content part
    public function pkp(string $lang, string $pkpString)
    {
        $permalink = app()->getLocale() === 'en' ? 'programs-clubs-and-projects' : 'programos-klubai-projektai';
        request()->route()->setParameter('permalink', $permalink);
        request()->merge(['permalink' => $permalink]);

        return $this->page();
    }

    public function calendarEvent(Calendar $calendar, LocationGeocoder $geocoder)
    {
        return $this->calendarEventMain('lt', $calendar, $geocoder);
    }

    public function calendarEventList(IndexPublicCalendarRequest $request)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL(
            $this->tenant->isMain() ? 'calendar.list' : 'tenant.calendar.list',
            $this->tenant->isMain() ? null : $this->subdomain,
        );

        $now = Carbon::now();
        $perPage = 20; // Number of events per page
        $tab = $request->validated('tab', 'upcoming');

        // Create base query with common filters
        $query = Calendar::query()
            ->with(['eventType', 'tenant:id,alias,shortname,fullname'])
            ->where('is_draft', false);

        // Filter by locale
        if (app()->getLocale() === 'en') {
            $query->where('is_international', true);
        }

        // Apply common filters from request parameters
        $this->applyCalendarFilters($query, $request);

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
                'public_url' => $event->publicUrl(app()->getLocale()),
            ]);

        $filterOptions = $this->getCalendarFilterOptions();

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Visų renginių sąrašas'),
            description: __('Vilniaus universiteto Studentų atstovybės ir bendruomenės renginių sąrašas.'),
        );

        // Share pagination SEO metadata for rel=next/prev links
        $this->sharePaginationSeoMeta($events, $this->tenant);

        return Inertia::render('Public/CalendarEventList', [
            'tenantSwitchTarget' => 'same-page',
            'events' => $events,
            'activeTab' => $tab,
            'allEventTypes' => $filterOptions['eventTypes'],
            'allTenants' => $filterOptions['tenants'],
        ]);
    }

    public function calendarListLegacy(string $lang)
    {
        return redirect(LocalizedRouteSlugs::route('calendar.list', request()->query(), $lang), 301);
    }

    /**
     * Tab switches on the calendar list happen client-side against Typesense, with no
     * Inertia reload — so these options must not be scoped to whichever tab was active at
     * the initial page load, or the filter would stay stuck on that tab's set (e.g. only
     * "VU SA" for "Upcoming") even after switching to "Past" in the browser.
     */
    private function getCalendarFilterOptions(): array
    {
        $eventTypes = EventType::query()
            ->whereHas('calendarEvents', function ($query): void {
                $query->where('is_draft', false);

                if (app()->getLocale() === 'en') {
                    $query->where('is_international', true);
                }
            })
            ->select('id', 'name')
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $tenants = Tenant::query()
            ->whereHas('calendar', function ($query): void {
                $query->where('is_draft', false);

                if (app()->getLocale() === 'en') {
                    $query->where('is_international', true);
                }
            })
            ->select('id', 'shortname')
            ->orderBy('shortname')
            ->get()
            ->toArray();

        return [
            'eventTypes' => $eventTypes,
            'tenants' => $tenants,
        ];
    }

    /**
     * Apply filters to calendar query
     */
    private function applyCalendarFilters(Builder $query, IndexPublicCalendarRequest $request): Builder
    {
        if ($request->validated('type') !== null) {
            $query->where('event_type_id', $request->validated('type'));
        }

        if ($request->validated('tenant') !== null) {
            $query->where('tenant_id', $request->validated('tenant'));
        }

        $search = $request->validated('search');
        if ($search !== null && $search !== '') {
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
        return redirect($calendar->publicUrl($lang) ?? route('calendar.list', ['lang' => $lang]), 301);
    }

    public function calendarCanonical(string $lang, string $calendarString, string $year, string $permalink, LocationGeocoder $geocoder, PublicUrlService $publicUrls)
    {
        $calendar = Calendar::query()
            ->whereYear('date', $year)
            ->where("permalink->{$lang}", $permalink)
            ->first();

        if ($calendar !== null) {
            return $this->calendarEventMain($lang, $calendar, $geocoder);
        }

        // No event currently holds this permalink — it may be one the event has since moved on
        // from, in which case public_urls still has the redirect history.
        $url = route('calendar.show', ['lang' => $lang, 'year' => $year, 'permalink' => $permalink]);
        $publicUrl = $publicUrls->resolve($url);
        $destination = $publicUrl === null ? null : $publicUrls->destinationFor($publicUrl);

        if ($destination === null) {
            abort(404);
        }

        return redirect($destination, 301);
    }

    public function calendarLegacy(string $lang, string $year, string $month, string $day, string $slug, LocationGeocoder $geocoder, PublicUrlService $publicUrls)
    {
        // Deliberately not backed by a stored row: date + title are still on the row, so this
        // stays resolvable forever without ever writing to public_urls.
        // Zero-padded: SQLite's whereDate() compares against strftime()'s zero-padded output and
        // silently misses "2026-4-5", even though MySQL tolerates it.
        $calendar = Calendar::query()
            ->whereDate('date', sprintf('%04d-%02d-%02d', $year, $month, $day))
            ->get()
            ->first(fn (Calendar $event) => Str::slug($event->getTranslation('title', $lang)) === $slug);

        if ($calendar === null) {
            abort(404);
        }

        return redirect($calendar->publicUrl($lang) ?? route('calendar.list', ['lang' => $lang]), 301);
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
            ->first(['id', 'title', 'date', 'permalink']);

        $toArray = function (?Calendar $event): ?array {
            if ($event === null) {
                return null;
            }

            return [
                ...$event->only(['id', 'title', 'date']),
                'public_url' => $event->publicUrl(app()->getLocale()),
            ];
        };

        return [$toArray($siblingsFor('previous')), $toArray($siblingsFor('next'))];
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
            ->with(['eventType', 'media', 'tenant:id,alias,shortname,fullname'])
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
            'public_url' => $event->publicUrl(app()->getLocale()),
        ])->all();
    }

    public function calendarEventMain($lang, Calendar $calendar, LocationGeocoder $geocoder)
    {
        $this->getBanners();
        $this->getTenantLinks();
        Inertia::share('otherLangURL', $calendar->publicUrl($this->getOtherLang()));

        $calendar->load(['tenant:id,alias,fullname,shortname', 'eventType']);

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
                'url' => $calendar->publicUrl($locale),
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
}
