<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Models\News;
use App\Models\Tag;
use App\Support\LocalizedRouteSlugs;
use Inertia\Inertia;

class NewsController extends PublicController
{
    public function news($subdomain, $lang, $newsString, $news)
    {
        $this->getBanners();
        $this->getTenantLinks();

        $news = News::query()->where([
            ['permalink', '=', $news],
            ['tenant_id', '=', $this->tenant->id],
        ])->firstOrFail();

        $other_lang_page = $news->other_language_news;

        Inertia::share('otherLangURL', $other_lang_page ? LocalizedRouteSlugs::route(
            'news',
            [
                'news' => $other_lang_page->permalink,
                'subdomain' => $this->subdomain,
            ],
            $other_lang_page->lang
        ) : null);

        // Get description for SEO, prioritizing 'short' field over tiptap content
        // Pass the news article's tenant for proper canonical URL
        $this->applyPageHead(
            contentTenant: $news->tenant,
            title: $news->title,
            description: ContentHelper::getDescriptionForSeo($news),
            author: $news->tenant->shortname,
            image: $news->getImageUrl(),
            publishedTime: $news->publish_time,
            modifiedTime: $news->updated_at,
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
                'url' => LocalizedRouteSlugs::route('news', [
                    'subdomain' => $this->subdomain,
                    'news' => $article->permalink,
                ], $article->lang),
            ]);

        // Generate breadcrumb schema
        $breadcrumbs = [
            [
                'name' => $lang === 'lt' ? 'Pradžia' : 'Home',
                'url' => route('home', ['subdomain' => $this->subdomain, 'lang' => $lang]),
            ],
            [
                'name' => $lang === 'lt' ? 'Naujienos' : 'News',
                'url' => LocalizedRouteSlugs::route('newsArchive', ['subdomain' => $this->subdomain], $lang),
            ],
            [
                'name' => $news->title,
                'url' => LocalizedRouteSlugs::route('news', [
                    'subdomain' => $this->subdomain,
                    'news' => $news->permalink,
                ], $lang),
            ],
        ];

        return Inertia::render('Public/NewsPage', [
            // See PublicPageController::page() — a link-list of related articles is
            // the most obvious use of the new dynamic block types inside a news body.
            'resolvedParts' => (object) $this->resolveContentParts($news->content),
            'article' => [
                ...$news->only('id', 'title', 'short', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'category', 'content', 'image_author', 'important', 'main_points', 'read_more', 'layout', 'show_breadcrumbs', 'highlights'),
                'tags' => $news->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'alias' => $tag->alias,
                ]),
                'content' => $news->content,
                /* 'content' => [ */
                /*    ...$news->content->toArray(), */
                /*    'parts' => $news->content->parts->map(function ($part) { */
                /*        return [ */
                /*            ...$part->parseTipTapElements()->toArray(), */
                /*        ]; */
                /*    }), */
                /* ], */
                // Use getImageUrl() for public display with fallback for missing images
                'image' => $news->getImageUrl(),
                'tenant' => $news->tenant->shortname,
            ],
            'relatedArticles' => $relatedArticles,
        ])->withViewData([
            'JSONLD_Schemas' => [
                $news->toNewsArticleSchema(),
                $this->getBreadcrumbSchema($breadcrumbs),
            ],
        ]);
    }

    public function newsArchive()
    {
        $this->getBanners();
        $this->getTenantLinks();

        Inertia::share('otherLangURL', LocalizedRouteSlugs::route('newsArchive', ['subdomain' => $this->subdomain], $this->getOtherLang()));

        $query = News::where('tenant_id', $this->tenant->id)
            ->where('lang', app()->getLocale())
            ->where('draft', false);

        // Filter by tag if provided
        if (request('tag')) {
            $query->whereHas('tags', function ($q): void {
                $tagParam = request('tag');
                // Try to find by alias first, fallback to ID if it's numeric
                $q->where('alias', $tagParam)
                    ->orWhere(function ($query) use ($tagParam): void {
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
            $currentTag = Tag::where('alias', $tagParam)
                ->orWhere(function ($query) use ($tagParam): void {
                    if (is_numeric($tagParam)) {
                        $query->where('id', $tagParam);
                    }
                })
                ->first();
        }

        // Pass the current tenant for proper canonical URL
        // Title suffix (" - <tenant>") is applied by applyPageHead(), so the org name
        // must not also appear at the front of the title here.
        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: $currentTag
                ? "Naujienos - {$currentTag->name}"
                : 'Naujienų archyvas',
            description: $currentTag
                ? "Naršyk per {$this->tenant->shortname} naujienas pagal žymą '{$currentTag->name}'"
                : "Naršyk per visas {$this->tenant->shortname} naujienas"
        );

        // Share pagination SEO metadata for rel=next/prev links
        $this->sharePaginationSeoMeta($news, $this->tenant);

        // Generate breadcrumb schema for archive
        $locale = app()->getLocale();
        $breadcrumbs = [
            [
                'name' => $locale === 'lt' ? 'Pradžia' : 'Home',
                'url' => route('home', ['subdomain' => $this->subdomain, 'lang' => $locale]),
            ],
            [
                'name' => $locale === 'lt' ? 'Naujienos' : 'News',
                'url' => LocalizedRouteSlugs::route('newsArchive', ['subdomain' => $this->subdomain], $locale),
            ],
        ];

        // Add tag to breadcrumb if filtered
        if ($currentTag) {
            $breadcrumbs[] = [
                'name' => $currentTag->name,
                'url' => LocalizedRouteSlugs::route('newsArchive', [
                    'subdomain' => $this->subdomain,
                    'tag' => $currentTag->alias,
                ], $locale),
            ];
        }

        return Inertia::render('Public/NewsArchive', [
            'news' => $news,
            'currentTag' => $currentTag,
        ])->withViewData(
            [
                'JSONLD_Schemas' => [$this->getBreadcrumbSchema($breadcrumbs)],
            ]
        );
    }
}
