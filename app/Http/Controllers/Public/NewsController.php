<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\PublicUrlService;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;

class NewsController extends PublicController
{
    public function news($subdomain, $lang, $newsString, $news, PublicUrlService $publicUrls)
    {
        $this->getBanners();
        $this->getTenantLinks();

        $news = News::query()->where([
            ['permalink', '=', $news],
            ['tenant_id', '=', $this->tenant->id],
        ])->first();

        if ($news === null) {
            $publicUrl = $publicUrls->resolve(request()->url());
            $destination = $publicUrl === null ? null : $publicUrls->destinationFor($publicUrl);

            if ($destination !== null) {
                return redirect($destination, 301);
            }

            abort(404);
        }

        $other_lang_page = $news->other_language_news;

        $this->sharePublicEditLink($news);

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

        // Fetch related articles from the same tenant. The shape matches `NewsItem`
        // (resources/js/Types/contentParts.ts) so they render through the same `NewsCard` as the
        // homepage's news block and the archive.
        $relatedArticles = News::where('tenant_id', $news->tenant_id)
            ->where('id', '!=', $news->id)
            ->where('lang', $news->lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(3)
            ->get(['id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang'])
            ->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'short' => $article->short,
                'lang' => $article->lang,
                'image' => $article->getImageUrl(),
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
                ...$news->only('id', 'title', 'short', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'content', 'image_author', 'important', 'main_points', 'read_more', 'show_breadcrumbs', 'highlights'),
                'tags' => $news->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'alias' => $tag->alias,
                ]),
                'content' => $news->content,
                'reading_time' => $news->readingTimeMinutes(),
                // getImageUrl() checks the file actually exists and returns null otherwise —
                // NewsArticleLayout skips the hero image entirely rather than show a placeholder.
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

        // Filter by tag if provided. `?tag=` arrives as either an alias (links built
        // server-side, e.g. NavigationLinkApiController) or a translated name (the
        // client-side Typesense filter — useNewsSearch's `tag_names:=[...]` condition
        // matches by name, and `parseUrlParams()` feeds this same raw param straight into
        // it once the page hydrates) — match either, so the initial SSR list agrees with
        // what the page filters down to a moment later.
        if (request('tag')) {
            $query->whereHas('tags', fn ($q) => $this->matchTagParam($q, request('tag')));
        }

        $news = $query
            ->select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang', 'created_at')
            ->orderBy('publish_time', 'desc')
            ->paginate(15)
            ->through(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'short' => $item->short,
                'image' => $item->getImageUrl(),
                'permalink' => $item->permalink,
                'publish_time' => $item->publish_time?->toISOString() ?? $item->created_at->toISOString(),
                'lang' => $item->lang,
            ]);

        $allTenants = Tenant::query()
            ->whereHas('news', function ($q): void {
                $q->where('draft', false)
                    ->where('lang', app()->getLocale());
            })
            ->select('id', 'shortname')
            ->orderBy('shortname')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'shortname' => $t->shortname,
            ])
            ->toArray();

        // Get the current tag for display purposes
        $currentTag = null;
        if (request('tag')) {
            $currentTag = $this->matchTagParam(Tag::query(), request('tag'))->first();
        }

        $locale = app()->getLocale();
        $isLt = $locale === 'lt';

        // Pass the current tenant for proper canonical URL
        // Title suffix (" - <tenant>") is applied by applyPageHead(), so the org name
        // must not also appear at the front of the title here.
        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: $currentTag
                ? ($isLt ? "Naujienos - {$currentTag->name}" : "News - {$currentTag->name}")
                : ($isLt ? 'Naujienų archyvas' : 'News Archive'),
            description: $currentTag
                ? ($isLt
                    ? "Naršyk per {$this->tenant->shortname} naujienas pagal žymą '{$currentTag->name}'"
                    : "Browse {$this->tenant->shortname} news tagged with '{$currentTag->name}'")
                : ($isLt
                    ? "Naršyk per visas {$this->tenant->shortname} naujienas"
                    : "Browse all {$this->tenant->shortname} news")
        );

        // Share pagination SEO metadata for rel=next/prev links
        $this->sharePaginationSeoMeta($news, $this->tenant);

        // Generate breadcrumb schema for archive
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
            'tenantSwitchTarget' => 'same-page',
            'news' => $news,
            'currentTag' => $currentTag,
            'allTenants' => $allTenants,
        ])->withViewData(
            [
                'JSONLD_Schemas' => [$this->getBreadcrumbSchema($breadcrumbs)],
            ]
        );
    }

    /**
     * Matches `?tag=` against a Tag's alias, its current-locale translated name, or (for a
     * purely numeric value) its id — see the comment above `newsArchive()`'s filter for why
     * both alias and name have to work.
     *
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    private function matchTagParam(Builder $query, string $tagParam): Builder
    {
        return $query->where(function (Builder $query) use ($tagParam): void {
            // Local scopes have no "or" magic of their own (that only exists for real
            // `where*` builder methods) — the closure groups `whereJsonContainsLocale`
            // as its own single-condition OR branch instead.
            $query->where('alias', $tagParam)
                ->orWhere(fn (Builder $q) => $q->whereJsonContainsLocale('name', app()->getLocale(), $tagParam));

            if (is_numeric($tagParam)) {
                $query->orWhere('id', $tagParam);
            }
        });
    }
}
