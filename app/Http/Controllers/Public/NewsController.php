<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Models\News;
use Inertia\Inertia;

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

        // Get description for SEO, prioritizing 'short' field over tiptap content
        $seo = $this->shareAndReturnSEOObject(
            title: $news->title.' - '.$this->tenant->shortname,
            description: ContentHelper::getDescriptionForSeo($news),
            author: $news->tenant->shortname,
            image: $news->image,
            published_time: $news->publish_time,
            modified_time: $news->updated_at,
        );

        // Fetch related articles from the same tenant
        $relatedArticles = News::where('tenant_id', $news->tenant_id)
            ->where('id', '!=', $news->id)
            ->where('lang', $news->lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(3)
            ->get(['id', 'title', 'permalink', 'publish_time', 'lang'])
            ->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'permalink' => $article->permalink,
                'publish_time' => $article->publish_time,
                'url' => route('news', [
                    'subdomain' => $this->subdomain,
                    'lang' => $article->lang,
                    'newsString' => $article->lang === 'lt' ? 'naujiena' : 'news',
                    'news' => $article->permalink,
                ]),
            ]);

        return Inertia::render('Public/NewsPage', [
            'article' => [
                ...$news->only('id', 'title', 'short', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'category', 'content', 'image_author', 'important', 'main_points', 'read_more', 'layout', 'highlights'),
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
            'relatedArticles' => $relatedArticles,
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
