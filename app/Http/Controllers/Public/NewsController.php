<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\News;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Tiptap\Editor;

class NewsController extends PublicController
{
    public function news($subdomain, $lang, $newsString, News $news)
    {
        $this->getBanners();
        $this->getTenantLinks();

        $image = $news->getImageUrl();

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

        $seo = $this->shareAndReturnSEOObject(
            title: $news->title.' - '.$this->tenant->shortname,
            description: $firstTiptapElement ? Str::limit((new Editor)->setContent($firstTiptapElement->json_content)->getText(), 160) : null,
            author: $news->tenant->shortname,
            image: $news->image,
            published_time: $news->publish_time,
            modified_time: $news->updated_at,
        );

        return Inertia::render('Public/NewsPage', [
            'article' => [
                ...$news->only('id', 'title', 'short', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'category', 'content', 'image_author', 'important', 'main_points', 'read_more'),
                'tags' => $news->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'alias' => $tag->alias,
                    ];
                }),
                'content' => $news->content,
                /* 'content' => [ */
                /*    ...$news->content->toArray(), */
                /*    'parts' => $news->content->parts->map(function ($part) { */
                /*        return [ */
                /*            ...$part->parseTipTapElements()->toArray(), */
                /*        ]; */
                /*    }), */
                /* ], */
                'image' => $image,
                'tenant' => $news->tenant->shortname,
            ],
        ])->withViewData([
            'SEOData' => $seo,
            'JSONLD_Schemas' => [$news->toNewsArticleSchema()],
        ]);
    }

    public function newsArchive()
    {
        $this->getBanners();
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('newsArchive', ['lang' => $this->getOtherLang(), 'subdomain' => $this->subdomain, 'newsString' => app()->getLocale() === 'lt' ? 'news' : 'naujienos']));

        $query = News::where('tenant_id', $this->tenant->id)
            ->where('lang', app()->getLocale())
            ->where('draft', false);

        // Filter by tag if provided
        if (request('tag')) {
            $query->whereHas('tags', function ($q) {
                $tagParam = request('tag');
                // Try to find by alias first, fallback to ID if it's numeric
                $q->where('alias', $tagParam)
                    ->orWhere(function ($query) use ($tagParam) {
                        if (is_numeric($tagParam)) {
                            $query->where('id', $tagParam);
                        }
                    });
            });
        }

        $news = $query->select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')
            ->orderBy('publish_time', 'desc')
            ->paginate(15);

        // Get the current tag for display purposes
        $currentTag = null;
        if (request('tag')) {
            $tagParam = request('tag');
            // Try to find by alias first, fallback to ID if it's numeric
            $currentTag = \App\Models\Tag::where('alias', $tagParam)
                ->orWhere(function ($query) use ($tagParam) {
                    if (is_numeric($tagParam)) {
                        $query->where('id', $tagParam);
                    }
                })
                ->first();
        }

        $seo = $this->shareAndReturnSEOObject(
            title: $currentTag
                ? "{$this->tenant->shortname} naujienos - {$currentTag->name}"
                : "{$this->tenant->shortname} naujienų archyvas",
            description: $currentTag
                ? "Naršyk per {$this->tenant->shortname} naujienas pagal žymą '{$currentTag->name}'"
                : "Naršyk per visas {$this->tenant->shortname} naujienas"
        );

        return Inertia::render('Public/NewsArchive', [
            'news' => $news,
            'currentTag' => $currentTag,
        ])->withViewData(
            [
                'SEOData' => $seo,
            ]
        );
    }
}
