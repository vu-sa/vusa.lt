<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\Calendar;
use App\Models\DutyInstitution;
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
	}

	public function home()
	{

		// get last 4 news by publishing date
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		// dd($this->alias, $padalinys);

		$news = News::where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();

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
							return Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image);
						}
					},
					"important" => $news->important,
				];
			}),
		]);
	}

	public function news(Request $request)
	{
		$news = News::where('permalink', '=', request()->route('permalink'))->first();

		if (substr($news->image, 0, 4) == 'http') {
			$image = $news->image;
		} else {
			$image = Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image);
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
		]);
	}

	public function newsArchive(Request $request)

	{
		
		Inertia::share('alias', $this->alias);

		$news = News::select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')->orderBy('publish_time', 'desc')->paginate(15);
		// ddd($news);
		return Inertia::render('Public/NewsArchive', [
			'news' => $news
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
		$navigation_item = Navigation::where([['padalinys_id', '=', $padalinys->id], ['name', '=', $page->title]])->get()->first();

		// dd(request()->route('permalink'), request()->permalink, $page, $padalinys);

		if ($page == null) {
			// return 404
			abort(404);
		}

		// get four random pages
		$random_pages = Page::where([['padalinys_id', '=', $padalinys->id], ['lang', app()->getLocale()]])->get()->random(4);

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
			'random_pages' => $random_pages->map(function ($page) {
				return [
					'id' => $page->id,
					'title' => $page->title,
					'lang' => $page->lang,
					'alias' => $page->padalinys->alias,
					'permalink' => $page->permalink,
				];
			}),
		]);
	}

	public function contacts()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		$alias_contacts = [];
		$search_contacts = null;

		if (request()->name) {
			$inputName = request()->name;
			$search_contacts = User::has('duties')->where('name', 'like', "%{$inputName}%")->get();
			// dd($contacts, request()->name);
		}

		if ($padalinys->id === 16) {
			$duty_institution = DutyInstitution::where('alias', '=', 'centrinis-biuras')->first();
		} else {
			$duty_institution = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->first();
		}

		// else {

		// 	$duty_institutions = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->get();

		// 	foreach ($duty_institutions as $key1 => $institution) {
				foreach ($duty_institution->duties as $key2 => $duty) {
					foreach ($duty->users as $key3 => $user) {
						if ($user->has('duties')) {
							array_push($alias_contacts, $user);
						}
					}
				}
		// 	}

		$alias_contacts = collect($alias_contacts)->unique();

		// dd($alias_contacts);
		// }

		// dd($contacts, request()->name);

		Inertia::share('alias', $padalinys->alias);
		return Inertia::render('Public/Contacts', [
			'alias_contacts' => $alias_contacts->map(function ($contact) use ($duty_institution) {
				return [
					'id' => $contact->id,
					'name' => $contact->name,
					'email' => $contact->email,
					'phone' => $contact->phone,
					'duties' => $contact->duties, //->only($duty_institution->id),
					'image' => function () use ($contact) {
						if (substr($contact->profile_photo_path, 0, 4) == 'http') {
							return $contact->profile_photo_path;
						} else if (is_null($contact->profile_photo_path)) {
							return null;
						} else {
							return Storage::get($contact->profile_photo_path) == null ? null : Storage::url($contact->profile_photo_path);
						}
					},
				];
			}),
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
							return Storage::get($contact->profile_photo_path) == null ? null : Storage::url($contact->profile_photo_path);
						}
					}
				];
			}),
		]);
	}

	public function search() {
		
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

	public function ataskaita2022() {

		$permalink = request()->route('permalink');

		// case for permalink, render Inertia
		
	}
}
