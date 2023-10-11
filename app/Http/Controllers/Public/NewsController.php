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

        $other_lang_page = $news->other_language_news;

        Inertia::share('otherLangURL', $other_lang_page ? route(
            'news',
            [
                'news' => $other_lang_page->permalink,
                'lang' => $other_lang_page->lang,
                'newsString' => $other_lang_page->lang === 'lt' ? 'naujiena' : 'news',
                'subdomain' => $this->subdomain,
            ]
        ) : null);

        return Inertia::render('Public/NewsPage', [
            'article' => [
                ...$news->only('id', 'title', 'short', 'text', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'category', 'content', 'image_author', 'important', 'main_points', 'read_more'),
                'tags' => $news->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }),
                'image' => $image,
                'padalinys' => $news->padalinys->shortname,
            ],
        ])->withViewData([
            'title' => $news->title.' | '.$this->padalinys->shortname,
            'description' => $news->short ? strip_tags($news->short) : strip_tags($news->text),
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
