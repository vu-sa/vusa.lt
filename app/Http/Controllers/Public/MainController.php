<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\Calendar;
use App\Models\DutyInstitution;
use App\Models\DutyType;
use App\Models\MainPage;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use App\Models\PageView;
use Illuminate\Support\Facades\Schema;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use Illuminate\Support\Str;

class MainController extends Controller
{
	public function __construct()
	{
		if (request()->lang) {
			App::setLocale(request()->lang);
		}

		// get subdomain if exists
		$host = Request::server('HTTP_HOST');

		if ($host !== 'localhost') {
			$subdomain = explode('.', $host)[0];
			$this->alias = $subdomain == 'www' ? '' : $subdomain;
			$this->alias = $subdomain == 'vusa' ? '' : $this->alias;
			$this->alias = $subdomain == 'naujas' ? '' : $this->alias;
			$this->alias = $subdomain == 'static' ? '' : $this->alias;
			$this->alias = Route::currentRouteName() == 'home' ? '' : $this->alias;
			$this->alias = Route::currentRouteName() == 'padalinys.home' ? '' : $this->alias;
		} else {
			$this->alias = '';
		}


		if (request()->padalinys != null) {
			$this->alias = in_array(request()->padalinys, ["Padaliniai", "naujas"]) ? '' : request()->padalinys;
		}

		$vusa = Padalinys::where('shortname', 'VU SA')->first();
		$mainNavigation = Navigation::where([['padalinys_id', $vusa->id], ['lang', app()->getLocale()]])->orderBy('order')->get();
		// dd($this->alias);
		/// pakeisti visur alias į vusa kad būtų aiškiau nes čia dabar nesąmonė


		if ($this->alias !== '') {
			$banners = Padalinys::where('alias', $this->alias)->first()->banners()->inRandomOrder()->where('is_active', 1)->get();
		} else {
			$banners = collect([]);
		}

		$banners = $banners->merge(Padalinys::where('alias', '')->first()->banners()->inRandomOrder()->where('is_active', 1)->get());
		Inertia::share('banners', $banners);
		Inertia::share('mainNavigation', $mainNavigation);

		// if table exists in database
		if (Schema::hasTable('page_views')) {
			PageView::createViewLog();
		}
	}

	public function home()
	{

		// get last 4 news by publishing date
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		// dd($this->alias, $padalinys);

		$news = News::where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->where('publish_time', '<=', date('Y-m-d H:i:s'))->orderBy('publish_time', 'desc')->take(4)->get();

		Inertia::share('alias', $this->alias);
		return Inertia::render('Public/Home', [
			'news' => $news->map(function ($news) {
				return [
					'id' => $news->id,
					'title' => $news->title,
					'lang' => $news->lang,
					'alias' => $news->padalinys->alias,
					'publish_time' => $news->publish_time,
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
			'main_page' => MainPage::where('padalinys_id', '=', $padalinys->id)->get(),
		])->withViewData([
			'description' => 'Vilniaus universiteto Studentų atstovybė (VU SA) – seniausia ir didžiausia Lietuvoje visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija'
		]);
	}

	public function news(Request $request)
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

		// Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image);

		Inertia::share('alias', $news->padalinys->alias);
		return Inertia::render('Public/News', [
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
		])->withViewData([
			'title' => $news->title,
			'description' => strip_tags($news->short),
			'image' => $image,
		]);
	}

	public function newsArchive(Request $request)

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

		// ddd($this->alias, Route::currentRouteName() == 'padalinys.home', request()->padalinys);

		$page = Page::where([['permalink', '=', request()->permalink], ['padalinys_id', '=', $padalinys->id]])->first();

		if ($page == null) {
			// return 404
			abort(404);
		}

		// dd($page);

		$navigation_item = Navigation::where([['padalinys_id', '=', $padalinys->id], ['name', '=', $page->title]])->get()->first();

		// dd(request()->route('permalink'), request()->permalink, $page, $padalinys);



		// get four random pages
		// $random_pages = Page::where([['padalinys_id', '=', $padalinys->id], ['lang', app()->getLocale()]])->get()->random(4);

		Inertia::share('alias', $page->padalinys->alias);
		return Inertia::render('Public/Page', [
			'navigation_item_id' => $navigation_item?->id,
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
			// 'random_pages' => $random_pages->map(function ($page) {
			// 	return [
			// 		'id' => $page->id,
			// 		'title' => $page->title,
			// 		'lang' => $page->lang,
			// 		'alias' => $page->padalinys->alias,
			// 		'permalink' => $page->permalink,
			// 	];
			// }),
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

		$duties_institutions = DutyInstitution::whereHas('duties')->where([['padalinys_id', '=', $padalinys->id], ['alias', 'not like', '%studentu-atstovai%']])->get();

		// dd($duties_institutions);

		// check if institution length is 1, then just return that one institution contacts 

		if ($duties_institutions->count() == 1) {
			// redirect to institution page
			return redirect('kontaktai/' . $duties_institutions->first()->alias);
		}

		return Inertia::render('Public/Contacts/Category', [
			'institutions' => $duties_institutions
		]);
	}

	public function contacts()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();
		Inertia::share('alias', $padalinys->alias);

		$alias = request()->alias;

		if (in_array($alias, [null, 'koordinatoriai', 'kuratoriai', 'studentu-atstovai'])) {
			$duty_type = DutyType::where('alias', '=', $alias ?? "koordinatoriai")->first();
			$child_duty_types = DutyType::where('pid', '=', $duty_type->id)->get();

			if ($padalinys->id === 16) {
				$duty_institution = DutyInstitution::where('alias', '=', 'centrinis-biuras')->first();
			} else {
				$duty_institution = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->first();
			}

			$alias_duties = collect([]);

			foreach ($child_duty_types as $child_duty_type) {
				$alias_duties = $alias_duties->merge($duty_institution->duties->where('type_id', '=', $child_duty_type->id));
			}

			$alias_duties = $alias_duties->merge($duty_institution->duties->where('type_id', '=', $duty_type->id));
		} else {
			$duty_institution = DutyInstitution::where('alias', '=', $alias)->first();

			if (is_null($duty_institution)) {
				abort(404);
			}

			$alias_duties = $duty_institution->duties;
		}

		// dd($duty_institution, $alias_duties);

		$alias_contacts = [];

		foreach ($alias_duties as $key => $duty) {
			foreach ($duty->users as $key2 => $user) {
				if ($user->has('duties')) {
					array_push($alias_contacts, $user);
				}
			}
		}

		$alias_contacts = collect($alias_contacts)->unique();



		// if ($padalinys->id === 16) {
		// 	$duty_institution = DutyInstitution::where('alias', '=', 'centrinis-biuras')->first();
		// } else {
		// 	$duty_institution = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->first();
		// }

		// else {

		// 	$duty_institutions = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->get();

		// 	foreach ($duty_institutions as $key1 => $institution) {
		// foreach ($duty_institution->duties as $key2 => $duty) {
		// 	foreach ($duty->users as $key3 => $user) {
		// 		if ($user->has('duties')) {
		// 			array_push($alias_contacts, $user);
		// 		}
		// 	}
		// }
		// 	}

		// $alias_contacts = collect($alias_contacts)->unique();

		return Inertia::render('Public/Contacts/Contacts', [
			'institution' => $duty_institution,
			'contacts' => $alias_contacts->map(function ($contact) use ($duty_institution) {
				return [
					'id' => $contact->id,
					'name' => $contact->name,
					'email' => $contact->email,
					'phone' => $contact->phone,
					'duty' => $contact->duties->where('institution_id', '=', $duty_institution->id)->first(),
					'image' => function () use ($contact) {
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
			// dd($contacts, request()->name);
		}

		Inertia::share('alias', $padalinys->alias);
		return Inertia::render('Public/Contacts/Search', [

			'search_contacts' => is_null($search_contacts) ? [] : $search_contacts->map(function ($contact) {

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
					'image' => function () use ($contact) {
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
		$calendar = Calendar::where('title', 'like', "%{$search}%")->orderBy('date', 'desc')->take(5)->get(['title', 'date', 'id']);

		// search news by title and get 5 most recent with only title, publish_time and id and permalink
		$news = News::where('title', 'like', "%{$search}%")->orderBy('publish_time', 'desc')->take(5)->get(['title', 'publish_time', 'id', 'permalink', 'lang']);

		// search pages by title and get 5 most recent with only title, id and permalink
		$pages = Page::where('title', 'like', "%{$search}%")->orderBy('created_at', 'desc')->take(5)->get(['title', 'id', 'permalink', 'lang']);

		// dd($calendar, $news, $pages);

		return back()->with('search_calendar', $calendar)->with('search_news', $news)->with('search_pages', $pages);
	}

	public function saziningaiExamRegistration()
	{

		// return all padalinys but only shortname VU and id
		$padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU')->orderBy('shortname')->get();

		return Inertia::render('Public/SaziningaiExamRegistration', [
			'padaliniaiOptions' => $padaliniai,
		])->withViewData([
			'title' => 'Programos „Sąžiningai“ atsiskaitymų registracija',
			'description' => 'Prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios, kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į saziningai@vusa.lt',
		]);
	}

	public function storeSaziningaiExamRegistration()
	{
		// dd(request()->all());

		$request = request();

		$saziningaiExam = SaziningaiExam::create([
			'uuid' => bin2hex(random_bytes(15)),
			'subject_name' => $request->subject_name,
			'name' => $request->name,
			'padalinys_id' => $request->unit,
			'place' => $request->place,
			'email' => $request->email,
			'duration' => $request->duration,
			'exam_holders' => $request->holders,
			'exam_type' => $request->type,
			'phone' => $request->phone,
			'students_need' => $request->students_need,
		]);

		// dd($saziningaiExam);

		// Store new flow
		foreach ($request->flows as $flow) {
			// dd($flow['time'], date('Y-m-d H:i:s', strtotime($flow['time'])));
			$saziningaiExamFlow = new SaziningaiExamFlow();
			$saziningaiExamFlow->exam_uuid = $saziningaiExam->uuid;
			$saziningaiExamFlow->start_time = date('Y-m-d H:i:s', strtotime($flow['time']));
			$saziningaiExamFlow->save();
		}

		// dd($saziningaiExam, $saziningaiExamFlow);

		return redirect()->route('home');
	}

	public function saziningaiExams()
	{

		// return all padalinys but only shortname VU and id
		$padaliniai = Padalinys::select('id', 'shortname_vu')->where('shortname', '!=', 'VU')->orderBy('shortname')->get();

		// return all exams that have their flows +1 day

		// $saziningaiExams = SaziningaiExam::with('flows')->whereRelation('flows', 'start_time', '>=', now()->subDay())->orderBy('created_at', 'desc')->get();
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
					'unit' => $saziningaiExamFlow->exam->padalinys->shortname_vu,
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

		return redirect()->back();
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
