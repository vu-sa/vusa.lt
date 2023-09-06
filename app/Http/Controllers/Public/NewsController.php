<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NewsController extends PublicController
{
    public function news($subdomain, $lang, $newsString, News $news)
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        if (substr($news->image, 0, 4) == 'http') {
            $image = $news->image;
        } else {
            $image = Storage::get(str_replace('uploads', 'public', $news->image)) === null ? '/images/icons/naujienu_foto.png' : $news->image;
        }

        $other_lang_news = $news->other_lang_id == null ? null : News::where('id', '=', $news->other_lang_id)->select('id', 'lang', 'permalink')->first();

        Inertia::share('alias', $news->padalinys->alias);
        Inertia::share('sharedOtherLangPage', $news->getOtherLanguage());

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
            'otherLangNews' => $news->getOtherLanguage(),
        ])->withViewData([
            'title' => $news->title,
            'description' => strip_tags($news->short),
            'image' => $image,
        ]);
    }

    public function newsArchive()
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        $news = News::where('padalinys_id', $this->padalinys->id)
            ->select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')
            ->orderBy('publish_time', 'desc')
            ->paginate(15);

        return Inertia::render('Public/NewsArchive', [
            'news' => $news,
        ])->withViewData([
            'title' => "{$this->padalinys->shortname} naujienų archyvas",
            'description' => "Naršyk per visas {$this->padalinys->shortname} naujienas",
        ]);
    }
}
