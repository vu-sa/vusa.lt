<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\SaziningaiExamFlow;
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
                    ->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category', 'extra_attributes', 'padalinys_id')->take(200)->get();
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category', 'extra_attributes', 'padalinys_id')->take(200)->get();
            });
        }
    }

    public function home()
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        // get last 4 news by publishing date
        $news = News::with('padalinys')->where([['padalinys_id', '=', $this->padalinys->id], ['lang', app()->getLocale()], ['draft', '=', 0]])
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
        }, SORT_DESC)->take(8)->values()->load('padalinys:id,alias,fullname,shortname');

        return Inertia::render('Public/HomePage', [
            'news' => $news->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'lang' => $news->lang,
                    'alias' => $news->padalinys->alias,
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
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('curatorRegistration');

        return Inertia::render('Public/CuratorRegistration', [
            'curatorPadaliniai' => (new CuratorRegistrationService)->getRegistrationPadaliniaiWithData(),
        ]);
    }

    public function page()
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        $page = Page::query()->where([['permalink', '=', request()->permalink], ['padalinys_id', '=', $this->padalinys->id]])->first();

        if ($page === null) {
            abort(404);
        }

        $navigation_item = Navigation::where([['padalinys_id', '=', $this->padalinys->id], ['name', '=', $page->title]])->get()->first();
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
                ...$page->only('id', 'title', 'content', 'lang', 'category', 'padalinys', 'permalink', 'other_lang_id'),
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
        $this->getPadalinysLinks();

        $category->load('pages:id,title,permalink,lang,category_id,padalinys_id')->load('pages.padalinys:id,alias');

        return Inertia::render('Public/CategoryPage', [
            'category' => $category->only('id', 'name', 'description', 'pages'),
        ]);
    }

    public function saziningaiExamRegistration()
    {
        $this->getBanners();
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('saziningaiExamRegistration');

        // return all padalinys but only shortname VU and id
        $padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        return Inertia::render('Public/SaziningaiExamRegistration', [
            'padaliniaiOptions' => $padaliniai,
        ])->withViewData([
            'title' => 'Programos „Sąžiningai“ atsiskaitymų registracija',
            'description' => 'Prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios, kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į saziningai@vusa.lt',
        ]);
    }

    public function saziningaiExams()
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        // return all padalinys but only shortname VU and id
        $padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        // return all exams that have their flows +1 day

        $saziningaiExamFlows = SaziningaiExamFlow::where('start_time', '>=', now()->subDay())->orderBy('start_time', 'asc')->get();
        $this->shareOtherLangURL('saziningaiExams.registered', saziningaiExams: $saziningaiExamFlows);

        return Inertia::render('Public/SaziningaiExams', [
            'padaliniaiOptions' => $padaliniai,
            'saziningaiExamFlows' => $saziningaiExamFlows->map(function ($saziningaiExamFlow) {
                return [
                    'key' => $saziningaiExamFlow->id,
                    'exam_uuid' => $saziningaiExamFlow->exam_uuid,
                    'start_time' => $saziningaiExamFlow->start_time,
                    // get observers count
                    'observers_registered' => $saziningaiExamFlow->observers->count(),
                    'exam' => $saziningaiExamFlow->exam->only(['subject_name', 'place', 'duration', 'exam_holders', 'exam_type', 'students_need']),
                    'unit' => $saziningaiExamFlow->exam->padalinys?->shortname_vu,
                ];
            }),
        ])->withViewData([
            'title' => 'Programos „Sąžiningai“ užregistruoti egzaminai',
            'description' => 'Registruokitės į egzaminų ar atsiskaitymų stebėjimą! Registruotis reikia į kiekvieną srautą atskirai.',
        ]);
    }

    public function summerCamps($lang, $year = null)
    {
        $this->getBanners();
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('pirmakursiuStovyklos');

        if ($year == null) {
            $year = intval(date('Y'));
        } else {
            $year = intval($year);
        }

        // TODO: add alias in global settings instead
        $events = Calendar::query()->whereHas('category', function (Builder $query) {
            $query->where('alias', '=', 'freshmen-camps');
        })->with('padalinys:id,alias,fullname')->whereYear('date', $year ?? date('Y'))
            ->with(['media'])->get()->sortBy('padalinys.alias');

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
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('individualStudies');

        return Inertia::render('Public/IndividualStudies')->withViewData([
            'title' => 'Individualios studijos',
            'description' => 'Nuo 2023 m. Vilniaus universitete kiekvienas naujai įstojęs (-usi) bakalauro ar vientisųjų studijų programos studentas (-ė) turi galimybę dėlioti savo studijas pagal asmeninius interesus, pasinaudodas (-a) individualių studijų galimybe. Sužinok apie tai plačiau.',
        ]);
    }

    // dynamically grabs list of pkp
    public function pkp()
    {
        $typeSlug = 'pkp';
        $institutionService = new InstitutionService();
        $this->getBanners();
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('pkp');

        $institutions = $institutionService->getInstitutionsByTypeSlug($typeSlug);

        return Inertia::render('Public/PKP', ['institutions' => $institutions])->withViewData([
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
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('calendar.event', calendarId: $calendar->id);

        $calendar->load('padalinys:id,alias,fullname,shortname');

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
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('memberRegistration');

        $padaliniai = Padalinys::select('id', 'fullname', 'shortname')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        return Inertia::render('Public/MemberRegistration', [
            'padaliniaiOptions' => $padaliniai,
        ])->withViewData([
            'title' => __('Prašymas tapti VU SA (arba VU SA PKP) nariu'),
            'description' => 'Naujų narių registracija',
        ]);
    }
}
