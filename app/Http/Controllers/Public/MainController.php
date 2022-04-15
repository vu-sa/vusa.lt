<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Tag;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class MainController extends Controller
{
	public function home($lang = 'lt')
	{

		// get last 4 news by publishing date
		$padalinys = Padalinys::where('shortname', '=', "VU SA")->first();

		$news = News::where([['padalinys_id', '=', $padalinys->id], ['draft', '=', 0]])->orderBy('publish_time', 'desc')->take(4)->get();
		return Inertia::render('Public/Home', [
			'news' => $news->map(function ($news) {
				return [
					'id' => $news->id,
					'title' => $news->title,
					'lang' => $news->lang,
					'publish_time' => $news->publish_time,
					"permalink" => $news->permalink,
					'image' => Storage::get($news->image) == null ? '/images/icons/naujienu_foto.png' : Storage::url($news->image),
					"important" => $news->important,
				];
			}),
		]);
	}

	public function news($lang, $news_string, $permalink)
	{
		$news = News::where('permalink', '=', $permalink)->first();

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
}
