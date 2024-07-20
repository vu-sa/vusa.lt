<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Tiptap\Editor;

class NewsController extends PublicController
{
    public function news($subdomain, $lang, $newsString, News $news)
    {
        $this->getBanners();
        $this->getTenantLinks();

        if (substr($news->image, 0, 4) == 'http') {
            $image = $news->image;
            $seoImage = $news->image;
        } else {
            $image = Storage::get(str_replace('uploads', 'public', $news->image)) === null ? '/images/icons/naujienu_foto.png' : $news->image;
            // Make seo image absolute
            $seoImage = $news->image ? url($news->image) : null;
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

        // check if page->content->parts has type 'tiptap', if yes, use tiptap parser to get first part content (maybe enough for description)

        $firstTiptapElement = $news->content->parts->filter(function ($part) {
            return $part->type === 'tiptap';
        })->first();

        // Check if empty array
        // This comes up, when in news creation, user doesn't add any content to tiptap editor
        // It is initialised as an empty array, and when the ->setContent() method is called, it was throwing an error
        if ($firstTiptapElement->json_content === []) {
            $firstTiptapElement = null;
        }

        $seoDescription = $firstTiptapElement ? (new Editor)->setContent($firstTiptapElement->json_content)->getText() : null;

        return Inertia::render('Public/NewsPage', [
            'article' => [
                ...$news->only('id', 'title', 'short', 'contents', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'category', 'content', 'image_author', 'important', 'main_points', 'read_more'),
                'tags' => $news->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }),
                'image' => $image,
                'tenant' => $news->tenant->shortname,
            ],
        ])->withViewData([
            'title' => $news->title.' | '.$this->tenant->shortname,
            'description' => $news->short ? strip_tags($news->short) : $seoDescription,
            'image' => $seoImage,
        ]);
    }

    public function newsArchive()
    {
        $this->getBanners();
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('newsArchive', ['lang' => $this->getOtherLang(), 'subdomain' => $this->subdomain, 'newsString' => app()->getLocale() === 'lt' ? 'news' : 'naujienos']));

        $news = News::where('tenant_id', $this->tenant->id)
            ->where('lang', app()->getLocale())
            ->where('draft', false)
            ->select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')
            ->orderBy('publish_time', 'desc')
            ->paginate(15);

        return Inertia::render('Public/NewsArchive', [
            'news' => $news,
        ])->withViewData([
            'title' => "{$this->tenant->shortname} naujienų archyvas",
            'description' => "Naršyk per visas {$this->tenant->shortname} naujienas",
        ]);
    }
}
