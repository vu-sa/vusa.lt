<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ContentHelper;
use App\Http\Controllers\PublicController;
use App\Http\Requests\IndexPublicNewsArchiveRequest;
use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\NewsArticlePresenter;
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

        $response = Inertia::render('Public/NewsPage', [
            // Empty resolved content must serialize as {}.
            'resolvedParts' => (object) $this->resolveContentParts($news->content),
            'article' => NewsArticlePresenter::article($news, $imageUrl),
            'relatedArticles' => $relatedArticles,
        ]);

        $response->withViewData([
            'JSONLD_Schemas' => [
                $news->toNewsArticleSchema(),
                $this->getBreadcrumbSchema($breadcrumbs),
            ],
        ]);

        return $response;
    }

    public function newsArchive(IndexPublicNewsArchiveRequest $request)
    {
        $tag = $request->validated('tag');

        $this->getBanners();
        $this->getTenantLinks();

        Inertia::share('otherLangURL', LocalizedRouteSlugs::route('newsArchive', ['subdomain' => $this->subdomain], $this->getOtherLang()));

        $query = News::where('tenant_id', $this->tenant->id)
            ->where('lang', app()->getLocale())
            ->where('draft', false);

        // Match aliases and translated names so the initial list agrees with the browser filter.
        if (filled($tag)) {
            $query->whereHas('tags', function (Builder $query) use ($tag): void {
                $this->matchTagParam($query, $tag);
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
        if (filled($tag)) {
            $currentTag = $this->matchTagParam(Tag::query(), $tag)->first();
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

        $response = Inertia::render('Public/NewsArchive', [
            'tenantSwitchTarget' => 'same-page',
            'news' => $news,
            'currentTag' => $currentTag,
            'allTenants' => $allTenants,
        ]);

        $response->withViewData(
            [
                'JSONLD_Schemas' => [$this->getBreadcrumbSchema($breadcrumbs)],
            ]
        );

        return $response;
    }

    /** @return list<array{id: int, title: string, short: string, lang: string, image: string|null, image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, permalink: string|null, publish_time: string|null, url: string}> */
    private function relatedArticles(News $news): array
    {
        $articles = News::where('tenant_id', $news->tenant_id)
            ->where('id', '!=', $news->id)
            ->where('lang', $news->lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(3)
            ->with('media')
            ->get(['id', 'title', 'short', 'permalink', 'publish_time', 'lang']);

        return NewsArticlePresenter::relatedArticles($articles, $this->subdomain);
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
