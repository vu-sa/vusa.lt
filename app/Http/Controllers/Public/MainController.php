<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\MainPage;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use App\Models\Registration;
use App\Models\RegistrationForm;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Services\IcalendarService;
use App\Mail\ConfirmExamRegistration;
use App\Mail\ConfirmMemberRegistration;
use App\Mail\ConfirmObserverRegistration;
use App\Mail\InformSaziningaiAboutObserverRegistration;
use App\Mail\InformSaziningaiAboutRegistration;
use App\Models\Type;
use App\Notifications\MemberRegistered;
use Spatie\CalendarLinks\Link;
use Datetime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class MainController extends Controller
{
	public function __construct()
	{
		// get subdomain if exists
		$host = Request::server('HTTP_HOST');

		if ($host !== 'localhost') {
			// get subdomain, for example 'gmc' or other element
			$firstElement = explode('.', $host)[0];

			switch ($firstElement) {
				case 'naujas':
					$this->alias = 'vusa';
					break;

				case 'www':
					$this->alias = 'vusa';
					break;

				case 'static':
					$this->alias = 'vusa';
					break;
				
				default:
					$this->alias = $firstElement;
					break;
			}
		} else {
			$this->alias = 'vusa';
		}

		if (request()->padalinys != null) {
			$this->alias = in_array(request()->padalinys, ["Padaliniai", "naujas"]) ? '' : request()->padalinys;
		}

		// get main navigation
		$vusa = Padalinys::where('shortname', 'VU SA')->first();
		$mainNavigation = Navigation::where([['padalinys_id', $vusa->id], ['lang', app()->getLocale()]])->orderBy('order')->get();

		Inertia::share('mainNavigation', $mainNavigation);
	}

	private function getCalendarGoogleLink($calendarEvent, $en = false) {

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
			->address($calendarEvent->location ?? "")
			->google();

		return $googleLink;
	}

	public function home()
	{

		// get last 4 news by publishing date
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		$banners = Padalinys::where('alias', 'vusa')->first()->banners()->inRandomOrder()->where('is_active', 1)->get();

		$news = News::where([['padalinys_id', '=', $padalinys->id],['lang', app()->getLocale()], ['draft', '=', 0]])->where('publish_time', '<=', date('Y-m-d H:i:s'))->orderBy('publish_time', 'desc')->take(4)->get();

		if (app()->getLocale() === 'en') {
            $calendar = Calendar::where('extra_attributes->en->shown', 'true')->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'extra_attributes', 'category')->take(400)->get();
        } else {
            $calendar = Calendar::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'category')->take(400)->get();
        }

		Inertia::share('alias', $this->alias);
		return Inertia::render('Public/HomePage', [
			'news' => $news->map(function ($news) {
				return [
					'id' => $news->id,
					'title' => $news->title,
					'lang' => $news->lang,
					'alias' => $news->padalinys->alias,
					// publish time to date format YYYY-MM-DD HH:MM
					'publish_time' => date('Y-m-d H:i', strtotime($news->publish_time)),
					"permalink" => $news->permalink,
					'image' => function () use ($news) {
						if (substr($news->image, 0, 4) == 'http') {
							return $news->image;
						} else {
							return Storage::get(str_replace('uploads', 'public', $news->image)) == null ? '/images/icons/naujienu_foto.png' : $news->image;
						}
					},
					"important" => $news->important,
				];
			}),
			'calendar' => $calendar->map(function ($calendar) {

				return [
					'id' => $calendar->id,
					'date' => $calendar->date,
					'end_date' => $calendar->end_date,
					'title' => app()->getLocale() === 'en' ? ($calendar->extra_attributes['en']['title'] ?? $calendar->title) : $calendar->title,
					'category' => $calendar->category,
					'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale() === 'en')
				];
			}),
			'mainPage' => MainPage::where([['padalinys_id', $padalinys->id], ['lang', app()->getLocale()]])->get(),
			'banners' => $banners
		])->withViewData([
			'description' => 'Vilniaus universiteto Studentų atstovybė (VU SA) – seniausia ir didžiausia Lietuvoje visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija'
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
				"important" => $news->important,
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

	public function newsArchive()

	{
		Inertia::share('alias', $this->alias);

		$news = News::select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')->orderBy('publish_time', 'desc')->paginate(15);
		// ddd($news);
		return Inertia::render('Public/NewsArchive', [
			'news' => $news
		])->withViewData([
			'title' => 'Naujienų archyvas',
			'description' => 'Naujienų archyvas',
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
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		$page = Page::where([['permalink', '=', request()->permalink], ['padalinys_id', '=', $padalinys->id]])->first();

		if ($page == null) {
			abort(404);
		}

		$navigation_item = Navigation::where([['padalinys_id', '=', $padalinys->id], ['name', '=', $page->title]])->get()->first();
		$other_lang_page = $page->other_lang_id == null ? null : Page::where('id', '=', $page->other_lang_id)->select('id', 'lang', 'permalink')->first();

		Inertia::share('alias', $page->padalinys->alias);
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

	public function contactsCategory()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();
		Inertia::share('alias', $padalinys->alias);

		// Special case for 'padaliniai' alias, since it's a special category, fetched from 'padaliniai' table

		if (request()->alias == 'padaliniai') {
			$padaliniai = Padalinys::where('type', '=', 'padalinys')->orderBy('shortname')->get();
			$padaliniaiInstitutions = [];
			foreach ($padaliniai as $key => $padalinys) {
				$institution = Institution::where('alias', '=', $padalinys->alias)->first();
				// add institution to array
				array_push($padaliniaiInstitutions, $institution);
			}

			$duties_institutions = new EloquentCollection($padaliniaiInstitutions);
		} else {
			$duties_institutions = Institution::whereHas('duties')
				// TODO: Čia reikia aiškesnės logikos
				->when(
					!request()->alias,
					// If /kontaktai/{} or /kontaktai/kategorija/{}
					function ($query) use ($padalinys) {
						return $query->where([['padalinys_id', '=', $padalinys->id], ['alias', 'not like', '%studentu-atstovai%']]);
					},
					// If there's an alias in the url
					function ($query) {
						return $query->where('alias', '=', request()->alias);
					}
				)->get();
		}

		// check if institution array length is 1, then just return that one institution contacts.

		if ($duties_institutions->count() == 1) {
			// redirect to that one institution page
			return redirect('kontaktai/' . $duties_institutions->first()->alias);
		}

		return Inertia::render('Public/Contacts/Category', [
			'institutions' => $duties_institutions
		])->withViewData([
			'title' => 'Kontaktai',
			'description' => 'VU SA kontaktai',
		]);
	}

	public function contacts()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();
		Inertia::share('alias', $padalinys->alias);

		$alias = request()->alias;

		if ($alias == 'studentu-atstovai') {
			// get all student duty institutions that have type 'studentu-atstovu-organas' and is of the same padalinys as the current one
			$institutions = Institution::with(['duties.users'])->where([['padalinys_id', '=', $padalinys->id]])->whereHas('type', function (Builder $query) {
				$query->where('alias', 'studentu-atstovu-organas');
			})->get()->sortBy('name')->values();

			return Inertia::render('Public/Contacts/StudentRepresentatives', [
				'institutions' => $institutions
			])->withViewData([
				'title' => 'Studentų atstovai',
				'description' => 'VU SA studentų atstovai',
			]);
		}

		if (in_array($alias, [null, 'koordinatoriai', 'kuratoriai'])) {
			$duty_type = Type::where('slug', '=', $alias ?? "koordinatoriai")->first();
			$child_duty_types = Type::where('parent_id', '=', $duty_type->id)->get();

			if ($padalinys->id === 16) {
				$institution = Institution::where('alias', '=', 'centrinis-biuras')->first();
			} else {
				$institution = Institution::where('padalinys_id', '=', $padalinys->id)->first();
			}

			$alias_duties = collect([]);

			foreach ($child_duty_types as $child_duty_type) {

				$alias_duties = $alias_duties->merge($institution->duties->where('type_id', '=', $child_duty_type->id)->sortBy('order')->values());
			}

			$alias_duties = $alias_duties->merge($institution->duties->where('type_id', '=', $duty_type->id)->sortBy('order')->values());
		} else {
			$institution = Institution::where('alias', '=', $alias)->first();

			if (is_null($institution)) {
				abort(404);
			}

			$alias_duties = $institution->duties->sortBy('order')->values();
		}

		$alias_contacts = [];

		foreach ($alias_duties as $key => $duty) {
			foreach ($duty->users as $key2 => $user) {
				if ($user->has('duties')) {
					array_push($alias_contacts, $user);
				}
			}
		}

		$alias_contact_collection = new EloquentCollection($alias_contacts);

		$alias_contact_collection = $alias_contact_collection->unique();

		return Inertia::render('Public/Contacts/ContactsShow', [
			'institution' => $institution,
			'contacts' => $alias_contact_collection->map(function ($contact) use ($institution) {
				return [
					'id' => $contact->id,
					'name' => $contact->name,
					'email' => $contact->email,
					'phone' => $contact->phone,
					'duties' => $contact->duties->where('institution_id', '=', $institution->id),
					'profile_photo_path' => function () use ($contact) {
						if (substr($contact->profile_photo_path, 0, 4) == 'http') {
							return $contact->profile_photo_path;
						} else if (is_null($contact->profile_photo_path)) {
							return null;
						} else {
							return Storage::get(str_replace('uploads', 'public', $contact->profile_photo_path)) == null ? null : $contact->profile_photo_path;
						}
					},
				];
			})
		])->withViewData([
			'title' => $padalinys->name . ' kontaktai',
			// description html to plain text
			'description' => strip_tags($padalinys->description),
		]);
	}

	public function searchContacts()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();
		$search_contacts = null;

		if (request()->name) {
			$inputName = request()->name;
			$search_contacts = User::has('duties')->where('name', 'like', "%{$inputName}%")->get();
		}

		Inertia::share('alias', $padalinys->alias);
		return Inertia::render('Public/Contacts/ContactsSearch', [

			'searchContacts' => is_null($search_contacts) ? [] : $search_contacts->map(function ($contact) {

				return [
					'id' => $contact->id,
					'name' => $contact->name,
					'email' => $contact->email,
					'phone' => $contact->phone,
					'duties' => $contact->duties->map(function ($duty) {
						return [
							'id' => $duty->id,
							'name' => $duty->name,
							'institution' => $duty->institution->name,
							'type' => $duty->type->name ?? $duty->type->short_name,
							'description' => $duty->description,
							'email' => $duty->email,
						];
					}),
					'profile_photo_path' => function () use ($contact) {
						if (substr($contact->profile_photo_path, 0, 4) == 'http') {
							return $contact->profile_photo_path;
						} else if (is_null($contact->profile_photo_path)) {
							return null;
						} else {
							return Storage::get(str_replace('uploads', 'public', $contact->profile_photo_path)) == null ? null : $contact->profile_photo_path;
						}
					}
				];
			}),
		])->withViewData([
			'title' => 'Kontaktų paieška'
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
			'has_arrived' => 'neatvyko'
		]);

		Mail::to('saziningai@vusa.lt')->send(new InformSaziningaiAboutObserverRegistration($saziningaiExamObserver, $saziningaiExamFlow));
		Mail::to($saziningaiExamObserver->email)->send(new ConfirmObserverRegistration($saziningaiExamFlow));
	}

	// TODO: pakeisti seno puslapio nuorodą
	public function summerCamps() {
		
		// get events with category of freshmen camps
		$events = Calendar::whereHas('category', function (Builder $query) {
			$query->where('name', '=', 'Pirmakursių stovykla');
		})->with('padalinys:id,alias,fullname')->with(['media'])->get()->sortBy('padalinys.alias');
		
		return Inertia::render('Public/SummerCamps', ['events' => $events->makeHidden(['description', 'location', 'category', 'url', 'user_id', 'extra_attributes'])->values()->all()])->withViewData([
			'title' => 'Pirmakursių stovyklos',
			'description' => 'Universiteto tvarka niekada su ja nesusidūrusiam žmogui gali pasirodyti labai sudėtinga ir būtent dėl to jau prieš septyniolika metų Vilniaus universiteto Studentų atstovybė (VU SA) surengė pirmąją pirmakursių stovyklą.',
		]);
	}

	public function calendarEvent(Calendar $calendar) {
		return $this->calendarEventMain('lt', $calendar);
	}

	public function calendarEventMain($lang = null, Calendar $calendar) {
		
		$calendar->load('padalinys:id,alias,fullname,shortname');

		return Inertia::render('Public/CalendarEvent', 
		['event' => $calendar, 'images' => $calendar->getMedia('images'), 'googleLink' => $this->getCalendarGoogleLink($calendar, app()->getLocale() === 'en')])->withViewData([
			'title' => $calendar->title,
			'description' => strip_tags($calendar->description),
		]);
	}

	public function publicAllEventCalendar() {
		
		$ics = new IcalendarService;

		return response($ics->get())
    		->header('Content-Type', 'text/calendar; charset=utf-8');
	}

	public function memberRegistration() {
		$padaliniai = Padalinys::select('id', 'fullname', 'shortname')->where('shortname', '!=', 'VU SA')->orderBy('shortname')->get();
		
		return Inertia::render('Public/MemberRegistration', [
			'padaliniaiOptions' => $padaliniai,
		])->withViewData([
			'title' => 'Naujų narių registracija',
			'description' => 'Naujų narių registracija',
		]);
	}

	public function storeMemberRegistration() {
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
			$chairDuty = $registerPadalinys->duties->where('type_id', '1')->first();
			$chairPerson = $chairDuty->users->first();
			$chairEmail = $chairDuty->email;
		} else {
			switch ($data['whereToRegister']) {
				case 'hema':
					$registerLocation = 'HEMA (' . __('Istorinių Europos kovos menų klubas') . ')';
					$chairEmail = 'hema@vusa.lt';
					break;
				
				case 'jek':
					$registerLocation = 'VU' . __('Jaunųjų energetikų klubas');
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

	public function storeRegistration(RegistrationForm $registrationForm) {
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
				return Inertia::render('Public/Ataskaita2022/Content/0-EN');
			} else if ($permalink == 'sveikinimai') {
				return Inertia::render('Public/Ataskaita2022/Content/1-EN');
			} else if ($permalink == 'vu-sa') {
				return Inertia::render('Public/Ataskaita2022/Content/2-EN');
			} else if ($permalink == 'mvp') {
				return Inertia::render('Public/Ataskaita2022/Content/3-EN');
			} else if ($permalink == 'studijos') {
				return Inertia::render('Public/Ataskaita2022/Content/4-EN');
			} else if ($permalink == 'organizacija') {
				return Inertia::render('Public/Ataskaita2022/Content/5-EN');
			} else if ($permalink == 'bendruomene') {
				return Inertia::render('Public/Ataskaita2022/Content/6-EN');
			} else if ($permalink == 'sritys') {
				return Inertia::render('Public/Ataskaita2022/Content/7-EN');
			} else if ($permalink == 'padeka') {
				return Inertia::render('Public/Ataskaita2022/Content/8-EN');
			}

			return Inertia::render('Public/Ataskaita2022/Content/0-EN');
		}

		if ($permalink == 'pradzia') {
			return Inertia::render('Public/Ataskaita2022/Content/0-LT');
		} else if ($permalink == 'sveikinimai') {
			return Inertia::render('Public/Ataskaita2022/Content/1-LT');
		} else if ($permalink == 'vu-sa') {
			return Inertia::render('Public/Ataskaita2022/Content/2-LT');
		} else if ($permalink == 'mvp') {
			return Inertia::render('Public/Ataskaita2022/Content/3-LT');
		} else if ($permalink == 'studijos') {
			return Inertia::render('Public/Ataskaita2022/Content/4-LT');
		} else if ($permalink == 'organizacija') {
			return Inertia::render('Public/Ataskaita2022/Content/5-LT');
		} else if ($permalink == 'bendruomene') {
			return Inertia::render('Public/Ataskaita2022/Content/6-LT');
		} else if ($permalink == 'sritys') {
			return Inertia::render('Public/Ataskaita2022/Content/7-LT');
		} else if ($permalink == 'padeka') {
			return Inertia::render('Public/Ataskaita2022/Content/8-LT');
		}

		return Inertia::render('Public/Ataskaita2022/Content/0-LT');
	}
}
