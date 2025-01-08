<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Document;
use App\Models\Form;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
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
    private function getCalendarGoogleLink($calendarEvent)
    {
        // check if event date is after end date, if so, return null
        // TODO: check in frontend
        if ($calendarEvent->end_date && $calendarEvent->date > $calendarEvent->end_date) {
            return null;
        }

        $googleLink = Link::create(
            $calendarEvent->title,
            DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->date),
            $calendarEvent->end_date
                ? DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->end_date)
                : Carbon::parse($calendarEvent->date)->addHour()->toDateTime()
        )
            ->description(strip_tags($calendarEvent->description))
            ->address($calendarEvent->location ?? '')
            ->google();

        return $googleLink;
    }

    protected function getEventsForCalendar()
    {
        if (app()->getLocale() === 'en') {
            return Cache::remember('calendar_en', 60 * 30, function () {
                return Calendar::query()->with('category')->where('is_international', true)->where('is_draft', false)
                    ->orderBy('date', 'desc')->take(100)->get();
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::query()->with('category')->where('is_draft', false)->orderBy('date', 'desc')->take(100)->get();
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
        }, SORT_DESC)->take(8)->values();

        $seo = $this->shareAndReturnSEOObject(title: __('Pagrindinis puslapis').' - '.$this->tenant->shortname);

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
                    'title' => $calendar->title,
                    'category' => $calendar->category,
                    'googleLink' => $this->getCalendarGoogleLink($calendar),
                ];
            }),
            'upcomingEvents' => $upcomingEvents->map(function ($calendar) {
                return [
                    ...$calendar->toArray(),
                    'images' => $calendar->getMedia('images'),
                ];
            }),
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

        $page = Page::query()->where([['permalink', '=', request()->permalink], ['tenant_id', '=', $this->tenant->id]])->first();

        if ($page === null) {
            abort(404);
        }

        $navigation_item = Navigation::query()->where('name', $page->title)->first();
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

        $seo = $this->shareAndReturnSEOObject(
            title: $page->title.' - '.$this->tenant->shortname,
            description: $firstTiptapElement ? (new Editor)->setContent($firstTiptapElement->json_content)->getText() : null,
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

        $category->load('pages:id,title,permalink,lang,category_id,tenant_id')->load('pages.tenant:id,alias');

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
        })->with('tenant:id,alias,fullname')->whereYear('date', $year ?? date('Y'))
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
            title: __('Programos, klubai ir projektai').' - VU SA',
            description: 'VU SA buria daugiau nei 20 iniciatyvų: programų, klubų ir projektų, skatinančių studentų saviraišką'
        );

        return Inertia::render('Public/PKP', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                ];
            }),
        ])->withViewData([
            'SEOData' => $seo,
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

        $seo = $this->shareAndReturnSEOObject(
            title: $calendar->title.' - '.$this->tenant->shortname,
            // Replace " with empty string, because it breaks JSON-LD
            description: app()->getLocale() === 'lt' ? Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: '') : Str::of((strip_tags($calendar->description)))->limit(160)->replaceMatches(pattern: '/\"/', replace: ''),
            image: $calendar->getFirstMediaUrl('images'),
            published_time: $calendar->created_at,
            modified_time: $calendar->updated_at,
        );

        return Inertia::render('Public/CalendarEvent', [
            'event' => [
                ...$calendar->toArray(),
                'images' => $calendar->getMedia('images'),
            ],
            'calendar' => $this->getEventsForCalendar(),
            'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale()),
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

    public function documents()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('documents');

        $seo = $this->shareAndReturnSEOObject(
            title: __('Dokumentai').' - VU SA',
            description: 'VU SA dokumentai'
        );

        if (request()->all() === []) {
            $documents = Document::query()->with('institution')
                ->orderBy('document_date', 'desc');
        } else {

            $documents = Document::search(request()->q)->query(function (Builder $query) {
                $query->with('institution.tenant')->when(request()->has('tenants'), function (Builder $query) {
                    $query->whereHas('institution.tenant', fn ($query) => $query->whereIn('tenants.shortname', request()->tenants));
                })->when(request()->has('contentTypes'), function (Builder $query) {
                    $query->whereIn('content_type', request()->contentTypes);
                })->when(request()->has('language'), function (Builder $query) {
                    $query->where('language', request()->language);
                    // if has at least one of the dates: dateFrom or dateTo
                })->when(request()->has('dateFrom') || request()->has('dateTo'), function (Builder $query) {
                    $dateFrom = request()->dateFrom ? Carbon::parse(request()->dateFrom / 1000) : null;
                    $dateTo = request()->dateTo ? Carbon::parse(request()->dateTo / 1000) : null;

                    $query->when($dateFrom, function (Builder $query) use ($dateFrom) {
                        $query->where('document_date', '>=', $dateFrom);
                    })->when($dateTo, function (Builder $query) use ($dateTo) {
                        $query->where('document_date', '<=', $dateTo);
                    });
                });
            });
        }

        return Inertia::render('Public/ShowDocuments', [
            'documents' => $documents->where('is_active', true)->orderBy('document_date', 'desc')->paginate(20),
            // Filter null values from content_type
            'allContentTypes' => Document::query()->select('content_type')->whereNotNull('content_type')->distinct()->pluck('content_type')->sort()->values(),
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
