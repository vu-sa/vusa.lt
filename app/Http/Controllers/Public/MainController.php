<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Mail\ConfirmExamRegistration;
use App\Mail\ConfirmMemberRegistration;
use App\Mail\ConfirmObserverRegistration;
use App\Mail\InformSaziningaiAboutObserverRegistration;
use App\Mail\InformSaziningaiAboutRegistration;
use App\Models\Calendar;
use App\Models\MainPage;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\Registration;
use App\Models\RegistrationForm;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Services\IcalendarService;
use Datetime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\CalendarLinks\Link;

class MainController extends PublicController
{
    private function getCalendarGoogleLink($calendarEvent, $en = false)
    {
        // check if event date is after end date, if so, return null
        // TODO: check in frontend
        if ($calendarEvent->end_date && $calendarEvent->date > $calendarEvent->end_date) {
            return null;
        }

        $googleLink = Link::create($en ? ($calendarEvent?->extra_attributes['en']['title'] ?? $calendarEvent->title) : $calendarEvent->title,
            DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->date),
            $calendarEvent->end_date
                ? DateTime::createFromFormat('Y-m-d H:i:s', $calendarEvent->end_date)
                : Carbon::parse($calendarEvent->date)->addHour()->toDateTime())
            ->description($en
            ? (strip_tags(
                ($calendarEvent?->extra_attributes['en']['description'] ?? $calendarEvent->description)
                ?? $calendarEvent->description))
            : strip_tags($calendarEvent->description))
            ->address($calendarEvent->location ?? '')
            ->google();

        return $googleLink;
    }

    protected function getEventsForCalendar()
    {
        if (app()->getLocale() === 'en') {
            return Cache::remember('calendar_en', 60 * 30, function () {
                return Calendar::where('extra_attributes->en->shown', 'true')->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category')->take(200)->get();
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category')->take(200)->get();
            });
        }
    }

    public function home()
    {
        // get last 4 news by publishing date
        $banners = Padalinys::where('alias', 'vusa')->first()->banners()->inRandomOrder()->where('is_active', 1)->get();

        $news = News::with('padalinys')->where([['padalinys_id', '=', $this->padalinys->id], ['lang', app()->getLocale()], ['draft', '=', 0]])
            ->where('publish_time', '<=', date('Y-m-d H:i:s'))
            ->orderBy('publish_time', 'desc')
            ->take(4)
            ->get();

        $calendar = $this->getEventsForCalendar();

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
                    'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale() === 'en'),
                ];
            }),
            'mainPage' => MainPage::where([['padalinys_id', $this->padalinys->id], ['lang', app()->getLocale()]])->get(),
            'banners' => $banners,
        ])->withViewData([
            'description' => 'Vilniaus universiteto Studentų atstovybė (VU SA) – seniausia ir didžiausia Lietuvoje visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija',
        ]);
    }

    public function news()
    {
        $news = News::where('permalink', '=', request()->route('permalink'))->first();

        if ($news == null) {
            // 404
            abort(404);
        }

        if (substr($news->image, 0, 4) == 'http') {
            $image = $news->image;
        } else {
            $image = Storage::get(str_replace('uploads', 'public', $news->image)) == null ? '/images/icons/naujienu_foto.png' : $news->image;
        }

        $other_lang_news = $news->other_lang_id == null ? null : News::where('id', '=', $news->other_lang_id)->select('id', 'lang', 'permalink')->first();

        Inertia::share('alias', $news->padalinys->alias);
        Inertia::share('sharedOtherLangPage', $other_lang_news);

        return Inertia::render('Public/NewsPage', [
            'article' => [
                'id' => $news->id,
                'title' => $news->title,
                'short' => $news->short,
                'text' => $news->text,
                'lang' => $news->lang,
                'other_lang_id' => $news->other_lang_id,
                'permalink' => $news->permalink,
                'publish_time' => $news->publish_time,
                'category' => $news->category,
                'tags' => $news->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }),
                'content' => $news->content,
                'image' => $image,
                'image_author' => $news->image_author,
                'important' => $news->important,
                'padalinys' => $news->padalinys->shortname,
                'main_points' => $news->main_points,
                'read_more' => $news->read_more,
            ],
            'otherLangNews' => $other_lang_news,
        ])->withViewData([
            'title' => $news->title,
            'description' => strip_tags($news->short),
            'image' => $image,
        ]);
    }

    public function getMainNews()
    {
        // get last 4 news by publishing date
        $padalinys = Padalinys::where('shortname', '=', 'VU SA')->first();
        $mainNews = News::select('title', 'short', 'image')->where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();

        return response()->json($mainNews, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
        //
    }

    public function page()
    {
        // TODO: this rewrite needs to be done in .htaccess
        // if (request()->lang === null) {
        // 	return redirect('/' . config('app.locale') . '/' . request()->permalink, 301);
        // }

        $page = Page::where([['permalink', '=', request()->permalink], ['padalinys_id', '=', $this->padalinys->id]])->first();

        if ($page === null) {
            abort(404);
        }

        $navigation_item = Navigation::where([['padalinys_id', '=', $this->padalinys->id], ['name', '=', $page->title]])->get()->first();
        $other_lang_page = $page->other_lang_id == null ? null : Page::where('id', '=', $page->other_lang_id)->select('id', 'lang', 'permalink')->first();

        Inertia::share('sharedOtherLangPage', $other_lang_page);

        return Inertia::render('Public/ContentPage', [
            'navigationItemId' => $navigation_item?->id,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'short' => $page->short,
                'text' => $page->text,
                'lang' => $page->lang,
                'other_lang_id' => $page->other_lang_id,
                'permalink' => $page->permalink,
                'category' => $page->category,
                'padalinys' => $page->padalinys->shortname,
            ],
            'otherLangPage' => $other_lang_page,
        ])->withViewData([
            'title' => $page->title,
            // truncate text to first sentence
            'description' => Str::limit(strip_tags($page->text), 150),
        ]);
    }

    public function search()
    {
        // get search query
        $search = request()->data['input'];

        // search calendar events by title and get 5 most recent with only title, date and id, but spaces are not important
        $calendar = Calendar::search($search)->orderBy('date', 'desc')->take(5)->get()->map(function ($calendar) {
            return [
                'id' => $calendar->id,
                'title' => $calendar->title,
                'date' => $calendar->date,
                'permalink' => $calendar->permalink,
                'lang' => $calendar->lang,
            ];
        });

        // search news by title and get 5 most recent with only title, publish_time and id and permalink
        $news = News::search($search)->orderBy('publish_time', 'desc')->take(5)->get()->map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'publish_time' => $news->publish_time,
                'permalink' => $news->permalink,
                'lang' => $news->lang,
            ];
        });

        // search pages by title and get 5 most recent with only title, id and permalink
        $pages = Page::search($search)->orderBy('created_at', 'desc')->take(5)->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'permalink' => $page->permalink,
                'lang' => $page->lang,
            ];
        });

        return back()->with('search_calendar', $calendar)->with('search_news', $news)->with('search_pages', $pages);
    }

    public function saziningaiExamRegistration()
    {
        // return all padalinys but only shortname VU and id
        $padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        return Inertia::render('Public/SaziningaiExamRegistration', [
            'padaliniaiOptions' => $padaliniai,
        ])->withViewData([
            'title' => 'Programos „Sąžiningai“ atsiskaitymų registracija',
            'description' => 'Prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios, kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į saziningai@vusa.lt',
        ]);
    }

    public function storeSaziningaiExamRegistration()
    {
        $request = request();

        $saziningaiExam = SaziningaiExam::create([
            'uuid' => bin2hex(random_bytes(15)),
            'subject_name' => $request->subject_name,
            'name' => $request->name,
            'padalinys_id' => $request->padalinys_id,
            'place' => $request->place,
            'email' => $request->email,
            'duration' => $request->duration,
            'exam_holders' => $request->exam_holders,
            'exam_type' => $request->exam_type,
            'phone' => $request->phone,
            'students_need' => $request->students_need,
        ]);

        // Store new flow
        foreach ($request->flows as $flow) {
            $saziningaiExamFlow = new SaziningaiExamFlow();
            $saziningaiExamFlow->exam_uuid = $saziningaiExam->uuid;
            $saziningaiExamFlow->start_time = date('Y-m-d H:i:s', strtotime($flow['start_time']));
            $saziningaiExamFlow->save();
        }

        $firstFlow = $saziningaiExam->flows->first();

        Mail::to('saziningai@vusa.lt')->send(new InformSaziningaiAboutRegistration($saziningaiExam, $firstFlow));
        Mail::to($saziningaiExam->email)->send(new ConfirmExamRegistration($saziningaiExam, $firstFlow));

        return redirect()->route('saziningaiExams.registered');
    }

    public function saziningaiExams()
    {
        // return all padalinys but only shortname VU and id
        $padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        // return all exams that have their flows +1 day

        $saziningaiExamFlows = SaziningaiExamFlow::where('start_time', '>=', now()->subDay())->orderBy('start_time', 'asc')->get();

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

    public function storeSaziningaiExamObserver()
    {
        $request = request();

        $saziningaiExamFlow = SaziningaiExamFlow::find($request->flow);

        $saziningaiExamObserver = SaziningaiExamObserver::create([
            'exam_uuid' => $saziningaiExamFlow->exam_uuid,
            'flow' => $request->flow,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'padalinys_id' => $request->padalinys_id,
            'has_arrived' => 'neatvyko',
        ]);

        Mail::to('saziningai@vusa.lt')->send(new InformSaziningaiAboutObserverRegistration($saziningaiExamObserver, $saziningaiExamFlow));
        Mail::to($saziningaiExamObserver->email)->send(new ConfirmObserverRegistration($saziningaiExamFlow));
    }

    // TODO: pakeisti seno puslapio nuorodą
    public function summerCamps()
    {
        // get events with category of freshmen camps
        $events = Calendar::whereHas('category', function (Builder $query) {
            $query->where('name', '=', 'Pirmakursių stovykla');
        })->with('padalinys:id,alias,fullname')->with(['media'])->get()->sortBy('padalinys.alias');

        return Inertia::render('Public/SummerCamps', ['events' => $events->makeHidden(['description', 'location', 'category', 'url', 'user_id', 'extra_attributes'])->values()->all()])->withViewData([
            'title' => 'Pirmakursių stovyklos',
            'description' => 'Universiteto tvarka niekada su ja nesusidūrusiam žmogui gali pasirodyti labai sudėtinga ir būtent dėl to jau prieš septyniolika metų Vilniaus universiteto Studentų atstovybė (VU SA) surengė pirmąją pirmakursių stovyklą.',
        ]);
    }

    public function calendarEvent(Calendar $calendar)
    {
        return $this->calendarEventMain('lt', $calendar);
    }

    public function calendarEventMain($lang, Calendar $calendar)
    {
        $calendar->load('padalinys:id,alias,fullname,shortname');

        return Inertia::render('Public/CalendarEvent', [
            'event' => [
                ...$calendar->toArray(),
                'images' => $calendar->getMedia('images'),
            ],
            'calendar' => $this->getEventsForCalendar(),
            'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale() === 'en')])
                ->withViewData([
                    'title' => $calendar->title,
                    'description' => strip_tags($calendar->description),
                ]);
    }

    public function publicAllEventCalendar()
    {
        $ics = new IcalendarService;

        return response($ics->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function memberRegistration()
    {
        $padaliniai = Padalinys::select('id', 'fullname', 'shortname')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();

        return Inertia::render('Public/MemberRegistration', [
            'padaliniaiOptions' => $padaliniai,
        ])->withViewData([
            'title' => 'Naujų narių registracija',
            'description' => 'Naujų narių registracija',
        ]);
    }

    public function storeMemberRegistration()
    {
        // store registration
        // 1 registration is to MIF camp, 2 is for VU SA and PKP members

        $this->storeRegistration(RegistrationForm::find(2));

        $data = request()->all();
        $registerLocation = new Padalinys();
        $chairPerson = new User();

        // if whereToRegister is int, then it is a padalinys id
        if (is_int($data['whereToRegister'])) {
            $registerPadalinys = Padalinys::find($data['whereToRegister']);
            $registerLocation = __($registerPadalinys->fullname);
            $chairDuty = $registerPadalinys->duties()->whereHas('types', function ($query) {
                $query->where('slug', 'pirmininkas');
            })->first();
            $chairPerson = $chairDuty->users->first();
            $chairEmail = $chairDuty->email;
        } else {
            switch ($data['whereToRegister']) {
                case 'hema':
                    $registerLocation = 'HEMA ('.__('Istorinių Europos kovos menų klubas').')';
                    $chairEmail = 'hema@vusa.lt';
                    break;

                case 'jek':
                    $registerLocation = 'VU '.__('Jaunųjų energetikų klubas');
                    $chairEmail = 'vujek@jek.lt';
                    break;

                default:
                    abort(500);
                    break;
            }
        }

        // send mail to the registered person
        Mail::to($data['email'])->send(new ConfirmMemberRegistration($data, $registerLocation, $chairPerson, $chairEmail));
        Notification::send($chairPerson, new MemberRegistered($data, $registerLocation, $chairEmail));
    }

    public function storeRegistration(RegistrationForm $registrationForm)
    {
        $registration = new Registration;
        $registration->data = request()->all();
        $registration->registration_form_id = $registrationForm->id;
        $registration->save();
    }

    public function ataskaita2022()
    {
        $permalink = request()->route('permalink');

        // get current locale
        $locale = app()->getLocale();

        if ($locale == 'en') {
            if ($permalink == 'pradzia') {
                return Inertia::render('Ataskaita2022/Content/0-EN');
            } elseif ($permalink == 'sveikinimai') {
                return Inertia::render('Ataskaita2022/Content/1-EN');
            } elseif ($permalink == 'vu-sa') {
                return Inertia::render('Ataskaita2022/Content/2-EN');
            } elseif ($permalink == 'mvp') {
                return Inertia::render('Ataskaita2022/Content/3-EN');
            } elseif ($permalink == 'studijos') {
                return Inertia::render('Ataskaita2022/Content/4-EN');
            } elseif ($permalink == 'organizacija') {
                return Inertia::render('Ataskaita2022/Content/5-EN');
            } elseif ($permalink == 'bendruomene') {
                return Inertia::render('Ataskaita2022/Content/6-EN');
            } elseif ($permalink == 'sritys') {
                return Inertia::render('Ataskaita2022/Content/7-EN');
            } elseif ($permalink == 'padeka') {
                return Inertia::render('Ataskaita2022/Content/8-EN');
            }

            return Inertia::render('Ataskaita2022/Content/0-EN');
        }

        if ($permalink == 'pradzia') {
            return Inertia::render('Ataskaita2022/Content/0-LT');
        } elseif ($permalink == 'sveikinimai') {
            return Inertia::render('Ataskaita2022/Content/1-LT');
        } elseif ($permalink == 'vu-sa') {
            return Inertia::render('Ataskaita2022/Content/2-LT');
        } elseif ($permalink == 'mvp') {
            return Inertia::render('Ataskaita2022/Content/3-LT');
        } elseif ($permalink == 'studijos') {
            return Inertia::render('Ataskaita2022/Content/4-LT');
        } elseif ($permalink == 'organizacija') {
            return Inertia::render('Ataskaita2022/Content/5-LT');
        } elseif ($permalink == 'bendruomene') {
            return Inertia::render('Ataskaita2022/Content/6-LT');
        } elseif ($permalink == 'sritys') {
            return Inertia::render('Ataskaita2022/Content/7-LT');
        } elseif ($permalink == 'padeka') {
            return Inertia::render('Ataskaita2022/Content/8-LT');
        }

        return Inertia::render('Ataskaita2022/Content/0-LT');
    }
}
