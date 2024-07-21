<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\CuratorRegistrationService;
use App\Services\ResourceServices\InstitutionService;
use Datetime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\CalendarLinks\Link;
use Tiptap\Editor;

class PublicPageController extends PublicController
{
    // TODO: add all pages to dev seed
    private function getCalendarGoogleLink($calendarEvent, $locale = 'lt')
    {
        // check if event date is after end date, if so, return null
        // TODO: check in frontend
        if ($calendarEvent->end_date && $calendarEvent->date > $calendarEvent->end_date) {
            return null;
        }

        $googleLink = Link::create(
            $locale === 'en' ? ($calendarEvent?->extra_attributes['en']['title'] ?? $calendarEvent->title) : $calendarEvent->title,
            DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->date),
            $calendarEvent->end_date
                ? DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->end_date)
                : Carbon::parse($calendarEvent->date)->addHour()->toDateTime()
        )
            ->description($locale === 'en'
                ? (strip_tags(
                    ($calendarEvent?->extra_attributes['en']['description'] ?? $calendarEvent->description)
                        ?? $calendarEvent->description
                ))
                : strip_tags($calendarEvent->description))
            ->address($calendarEvent->location ?? '')
            ->google();

        return $googleLink;
    }

    protected function getEventsForCalendar()
    {
        if (app()->getLocale() === 'en') {
            return Cache::remember('calendar_en', 60 * 30, function () {
                return Calendar::where('extra_attributes->en->shown', 'true')
                    ->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category', 'extra_attributes', 'tenant_id')->take(200)->get();
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category', 'extra_attributes', 'tenant_id')->take(200)->get();
            });
        }
    }

    public function home()
    {
        $this->getBanners();
        $this->getTenantLinks();

        // get last 4 news by publishing date
        $news = News::with('tenant')->where([['tenant_id', '=', $this->tenant->id], ['lang', app()->getLocale()], ['draft', '=', 0]])
            ->where('publish_time', '<=', date('Y-m-d H:i:s'))
            ->orderBy('publish_time', 'desc')
            ->take(4)
            ->get();

        $calendar = $this->getEventsForCalendar();

        // get 4 upcoming events by end_date if it exists, otherwise by date
        $upcomingEvents = $calendar->filter(function ($event) {
            return $event->end_date ? $event->end_date > date('Y-m-d H:i:s') : $event->date > date('Y-m-d H:i:s');
        })->sortBy(function ($event) {
            return $event->date;
        }, SORT_DESC)->take(8)->values()->load('tenant:id,alias,fullname,shortname');

        return Inertia::render('Public/HomePage', [
            'news' => $news->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'lang' => $news->lang,
                    'alias' => $news->tenant->alias,
                    // publish time to date format YYYY-MM-DD HH:MM
                    'publish_time' => date('Y-m-d H:i', strtotime($news->publish_time)),
                    'permalink' => $news->permalink,
                    'image' => function () use ($news) {
                        if (substr($news->image, 0, 4) == 'http') {
                            return $news->image;
                        } else {
                            return Storage::get(str_replace('uploads', 'public', $news->image)) == null ? '/images/icons/naujienu_foto.png' : $news->image;
                        }
                    },
                    'important' => $news->important,
                ];
            }),
            'calendar' => $calendar->map(function ($calendar) {
                return [
                    'id' => $calendar->id,
                    'date' => $calendar->date,
                    'end_date' => $calendar->end_date,
                    'title' => app()->getLocale() === 'en' ? ($calendar->extra_attributes['en']['title'] ?? $calendar->title) : $calendar->title,
                    'category' => $calendar->category,
                    'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale()),
                ];
            }),
            'upcomingEvents' => $upcomingEvents->map(function ($calendar) {
                return [
                    ...$calendar->toArray(),
                    'images' => $calendar->getMedia('images'),
                ];
            }),
        ])->withViewData([
            'title' => 'Pagrindinis puslapis',
            'description' => 'Vilniaus universiteto Studentų atstovybė (VU SA) – seniausia ir didžiausia Lietuvoje visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija',
        ]);
    }

    public function curatorRegistration()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('curatorRegistration');

        return Inertia::render('Public/CuratorRegistration', [
            'curatorTenants' => (new CuratorRegistrationService)->getRegistrationTenantsWithData(),
        ]);
    }

    public function page()
    {
        // At first, since for PKP we want to redirect old pages to contacts page, we check in this function
        $pkps = (new InstitutionService)->getInstitutionsByTypeSlug('pkp');
        $institution = $pkps->firstWhere('alias', request()->permalink);

        if ($institution) {
            return redirect()->route('contacts.alias', ['subdomain' => $this->subdomain, 'lang' => app()->getLocale(), 'institution' => request()->permalink]);
        }

        // Continue with normal page rendering

        $this->getBanners();
        $this->getTenantLinks();

        $page = Page::query()->where([['permalink', '=', request()->permalink], ['tenant_id', '=', $this->tenant->id]])->first();

        if ($page === null) {
            abort(404);
        }

        $navigation_item = Navigation::where([['tenant_id', '=', $this->tenant->id], ['name', '=', $page->title]])->get()->first();
        $other_lang_page = $page->getOtherLanguage();

        Inertia::share('otherLangURL', $other_lang_page ? route(
            'page',
            [
                'subdomain' => $this->subdomain,
                'lang' => $other_lang_page->lang,
                'permalink' => $other_lang_page->permalink,
            ]
        ) : null);

        // check if page->content->parts has type 'tiptap', if yes, use tiptap parser to get first part content (maybe enough for description)

        $firstTiptapElement = $page->content->parts->filter(function ($part) {
            return $part->type === 'tiptap';
        })->first();

        $seoDescription = $firstTiptapElement ? (new Editor)->setContent($firstTiptapElement->json_content)->getText() : null;

        return Inertia::render('Public/ContentPage', [
            'navigationItemId' => $navigation_item?->id,
            'page' => [
                ...$page->only('id', 'title', 'content', 'lang', 'category', 'tenant', 'permalink', 'other_lang_id'),
                // TODO: It's possible to parse tiptap elements in server, but doesn't parse correctly all the time. Will debug later.
                // 'content' => [
                //     ...$page->content->toArray(),
                //     'parts' => $page->content->parts->map(function ($part) {
                //         return [
                //             ...$part->parseTipTapElements()->toArray(),
                //         ];
                //     }),
                // ],
            ],
        ])->withViewData([
            'title' => $page->title,
            'description' => Str::limit($seoDescription, 150),
        ]);
    }

    public function category($lang, Category $category)
    {
        $this->getBanners();
        $this->getTenantLinks();

        $category->load('pages:id,title,permalink,lang,category_id,tenant_id')->load('pages.tenant:id,alias');

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
        $events = Calendar::query()->whereHas('category', function (Builder $query) {
            $query->where('alias', '=', 'freshmen-camps');
        })->with('tenant:id,alias,fullname')->whereYear('date', $year ?? date('Y'))
            ->with(['media'])->get()->sortBy('tenant.alias');

        if ($events->isEmpty() && $year != intval(date('Y'))) {
            return redirect()->route('pirmakursiuStovyklos', ['lang' => app()->getLocale(), 'year' => null]);
        }

        $yearsWhenEventsExist = Calendar::query()->whereHas('category', function (Builder $query) {
            $query->where('alias', '=', 'freshmen-camps');
        })->selectRaw('YEAR(date) as year')->distinct()->get()->pluck('year');

        return Inertia::render('Public/SummerCamps',
            [
                'events' => $events->makeHidden(['description', 'location', 'category', 'url', 'user_id', 'extra_attributes'])->values()->all(),
                'year' => $year,
                'yearsWhenEventsExist' => $yearsWhenEventsExist,
            ])->withViewData([
                'title' => $year == intval(date('Y')) ? 'Pirmakursių stovyklos' : $year.' m. pirmakursių stovyklos',
                'description' => 'Universiteto tvarka niekada su ja nesusidūrusiam žmogui gali pasirodyti labai sudėtinga ir būtent dėl to jau prieš septyniolika metų Vilniaus universiteto Studentų atstovybė (VU SA) surengė pirmąją pirmakursių stovyklą.',
            ]);
    }

    public function individualStudies()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('individualStudies');

        return Inertia::render('Public/IndividualStudies')->withViewData([
            'title' => 'Individualios studijos',
            'description' => 'Nuo 2023 m. Vilniaus universitete kiekvienas naujai įstojęs (-usi) bakalauro ar vientisųjų studijų programos studentas (-ė) turi galimybę dėlioti savo studijas pagal asmeninius interesus, pasinaudodas (-a) individualių studijų galimybe. Sužinok apie tai plačiau.',
        ]);
    }

    // dynamically grabs list of pkp
    public function pkp()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('pkp');

        $institutions = (new InstitutionService)->getInstitutionsByTypeSlug('pkp')->where('is_active', true);

        return Inertia::render('Public/PKP', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                ];
            }),
        ])->withViewData([
            'title' => 'Programos, klubai ir projektai',
        ]);
    }

    public function calendarEvent(Calendar $calendar)
    {
        return $this->calendarEventMain('lt', $calendar);
    }

    public function calendarEventMain($lang, Calendar $calendar)
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('calendar.event', calendarId: $calendar->id);

        $calendar->load('tenant:id,alias,fullname,shortname');

        return Inertia::render('Public/CalendarEvent', [
            'event' => [
                ...$calendar->toArray(),
                'images' => $calendar->getMedia('images'),
            ],
            'calendar' => $this->getEventsForCalendar(),
            'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale()),
        ])
            ->withViewData([
                'title' => $calendar->title,
                'description' => strip_tags($calendar->description),
            ]);
    }

    public function memberRegistration()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('memberRegistration');

        $tenants = Tenant::select('id', 'fullname', 'shortname')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        return Inertia::render('Public/MemberRegistration', [
            'tenantOptions' => $tenants,
        ])->withViewData([
            'title' => __('Prašymas tapti VU SA (arba VU SA PKP) nariu'),
            'description' => 'Naujų narių registracija',
        ]);
    }
}
