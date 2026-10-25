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
use Illuminate\Support\Collection;
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
            ['draft', '=', false],
        ])->with(['tenant', 'content', 'tags', 'other_language_news'])->first();

        if ($news === null) {
            $publicUrl = $publicUrls->resolve(request()->url());
            $destination = $publicUrl === null ? null : $publicUrls->destinationFor($publicUrl);

            if ($destination !== null) {
                return redirect($destination, 301);
            }

            abort(404);
        }

        $other_lang_page = $news->other_language_news?->draft ? null : $news->other_language_news;

        // A filesystem check per call; the page needs it three times.
        $imageUrl = $news->getImageUrl();

        $this->sharePublicEditLink($news);

        Inertia::share('otherLangURL', $other_lang_page ? LocalizedRouteSlugs::route(
            'news',
            [
                'news' => $other_lang_page->permalink,
                'subdomain' => $this->subdomain,
            ],
            $other_lang_page->lang
        ) : null);

        $this->applyPageHead(
            contentTenant: $news->tenant,
            title: $news->title,
            description: ContentHelper::getDescriptionForSeo($news),
            author: $news->tenant->shortname,
            image: $imageUrl,
            publishedTime: $news->publish_time,
            modifiedTime: $news->updated_at,
        );

        $relatedArticles = $this->relatedArticles($news);

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
                ...$news->only('id', 'title', 'short', 'lang', 'other_lang_id', 'permalink', 'publish_time', 'content', 'important', 'main_points', 'read_more', 'show_breadcrumbs', 'highlights'),
                'image_author' => $news->imageData('image')['author'] ?? null,
                'tags' => $news->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'alias' => $tag->alias,
                ]),
                'content' => $news->content,
                'reading_time' => $news->readingTimeMinutes(),
                // getImageUrl() checks the file actually exists and returns null otherwise —
                // NewsArticleLayout skips the hero image entirely rather than show a placeholder.
                'image' => $imageUrl,
                'image_media' => $news->imageData('image'),
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

        // Match aliases and translated names so the initial list agrees with the browser filter.
        if (request('tag')) {
            $query->whereHas('tags', function (Builder $query): void {
                $this->matchTagParam($query, request('tag'));
            });
        }

        $news = $query
            ->select('id', 'title', 'short', 'permalink', 'publish_time', 'lang', 'created_at')
            ->with('media')
            ->orderBy('publish_time', 'desc')
            ->paginate(15)
            ->through(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'short' => $item->short,
                'image' => $item->getImageUrl(),
                'image_media' => $item->imageData('image'),
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

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: $currentTag
                ? ($isLt ? "Naujienos: {$currentTag->name}" : "News: {$currentTag->name}")
                : ($isLt ? 'Naujienų archyvas' : 'News Archive'),
            description: $currentTag
                ? [$currentTag->description, __('seo.topic_description', ['topic' => $currentTag->name, 'tenant' => $this->tenant->shortname])]
                : __('seo.news_archive_description'),
            canonicalUrl: $this->getCanonicalUrl($this->tenant, ['page', 'tag']),
        );

        // Share pagination SEO metadata for rel=next/prev links
        $this->sharePaginationSeoMeta($news, $this->tenant);

        $breadcrumbs = $this->archiveBreadcrumbs($currentTag);

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

    /** @return Collection<int, covariant array<string, mixed>> */
    private function relatedArticles(News $news): Collection
    {
        return News::where('tenant_id', $news->tenant_id)
            ->where('id', '!=', $news->id)
            ->where('lang', $news->lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(3)
            ->with('media')
            ->get(['id', 'title', 'short', 'permalink', 'publish_time', 'lang'])
            ->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'short' => $article->short,
                'lang' => $article->lang,
                'image' => $article->getImageUrl(),
                'image_media' => $article->imageData('image'),
                'permalink' => $article->permalink,
                'publish_time' => $article->publish_time,
                'url' => LocalizedRouteSlugs::route('news', [
                    'subdomain' => $this->subdomain,
                    'news' => $article->permalink,
                ], $article->lang),
            ]);
    }

    private function archiveBreadcrumbs(?Tag $currentTag): array
    {
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

        if ($currentTag) {
            $breadcrumbs[] = [
                'name' => $currentTag->name,
                'url' => LocalizedRouteSlugs::route('newsArchive', [
                    'subdomain' => $this->subdomain,
                    'tag' => $currentTag->alias,
                ], $locale),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    private function matchTagParam(Builder $query, string $tagParam): Builder
    {
        return $query->where(function (Builder $query) use ($tagParam): void {
            // Group the locale scope because Eloquent only supplies OR variants for where methods.
            $query->where('alias', $tagParam)
                ->orWhere(fn (Builder $q) => $q->whereJsonContainsLocale('name', app()->getLocale(), $tagParam));

            if (is_numeric($tagParam)) {
                $query->orWhere('id', $tagParam);
            }
        });
    }
}
