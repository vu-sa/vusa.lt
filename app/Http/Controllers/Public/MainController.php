<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use App\Models\Tag;
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
			$this->alias = request()->padalinys;
		}
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
	public function page()
	{
		$padalinys = Padalinys::where('alias', '=', $this->alias)->first();

		$page = Page::where([['permalink', '=', request()->route('permalink')], ['padalinys_id', '=', $padalinys->id]])->first();

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
}
