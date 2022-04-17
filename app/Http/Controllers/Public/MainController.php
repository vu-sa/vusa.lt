<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
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

class MainController extends Controller
{
	public function __construct()
	{
		// get subdomain if exists
		$host = Request::server('HTTP_HOST');

		if ($host !== 'localhost') {
			$subdomain = explode('.', $host)[0];
			$this->alias = $subdomain == 'www' ? '' : $subdomain;
			$this->alias = $subdomain == 'vusa' ? '' : $subdomain;
			$this->alias = Route::currentRouteName() == 'home' ? '' : $this->alias;
		} else {
			$this->alias = '';
		}

		if (request()->padalinys != null) {
			$this->alias = request()->padalinys == "Padaliniai" ? '' : request()->padalinys;
		}

		$vusa = Padalinys::where('shortname', 'VU SA')->first();
		$mainNavigation = Navigation::where([['padalinys_id', $vusa->id],['lang', 'lt']])->orderBy('order')->get();
		Inertia::share('mainNavigation' , $mainNavigation);
	}

	public function home($lang = 'lt')
	{

		// get last 4 news by publishing date
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

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
					'image' => Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image),
					"important" => $news->important,
				];
			}),
		]);
	}

	public function news(Request $request)
	{
		$news = News::where('permalink', '=', request()->route('permalink'))->first();

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
				'image' => Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image),
				'image_author' => $news->image_author,
				"important" => $news->important,
				'padalinys' => $news->padalinys->shortname,
				'main_points' => $news->main_points,
				'read_more' => $news->read_more,
			],
		]);
	}

	public function getMainNews() {
		// get last 4 news by publishing date
		$padalinys = Padalinys::where('shortname', '=', 'VU SA')->first();
		$mainNews = News::select('title', 'short', 'image')->where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();
		return response()->json($mainNews, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);
		// 
	}

	public function page()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		// ?? why route('permalink') is working

		$page = Page::where([['permalink', '=', request()->permalink], ['padalinys_id', '=', $padalinys->id]])->first();
		
		// dd(request()->route('permalink'), request()->permalink, $page, $padalinys);

		if ($page == null) {
			// return 404
			abort(404);
		}

		// get four random pages
		$random_pages = Page::where('padalinys_id', '=', $padalinys->id)->get()->random(4);

		Inertia::share('alias', $page->padalinys->alias);
		return Inertia::render('Public/Page', [
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
		
		if (request()->name) {
			$inputName = request()->name;
			$contacts = User::where('name', 'like', "%{$inputName}%")->get();
			// dd($contacts, request()->name);
		} else {
			
			$duty_institutions = DutyInstitution::where('padalinys_id', '=', $padalinys->id)->get();

			$contacts = [];

			foreach ($duty_institutions as $key1 => $institution) {
				foreach ($institution->duties as $key2 => $duty) {
					foreach ($duty->users as $key3 => $user) {
						array_push($contacts, $user);
					}
				}
			}

			$contacts = collect($contacts)->unique();
		}

		// dd($contacts, request()->name);

		Inertia::share('alias', $padalinys->alias);
		return Inertia::render('Public/Contacts', [
			'contacts' => is_null($contacts) ? [] : $contacts->map(function ($contact) {
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
					'image' => $contact->profile_photo_path == null ? null : Storage::get($contact->profile_photo_path),
				];
			}),
		]);
	}
}
