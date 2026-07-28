<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Models\Category;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Resolves `link-list` blocks: a handful of links to news, pages, or manually-typed
 * URLs, rendered as cards or a compact list (see RCLinkList/LinkListDisplay.vue).
 */
final class LinkListResolver implements ResolvesContentPart
{
    private const MAX_ITEMS = 12;

    private const MAX_PINNED_IDS = 12;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        // Batch the "pinned by id" sources across every link-list block on the page —
        // one query each, regardless of how many blocks reference news/pages by id.
        // `latest` mode stays a query per block: link-list blocks per page are rare
        // (a handful at most) and each has its own category/tenant/limit combination,
        // so batching them would trade a clear implementation for a saving that never
        // materializes in practice.
        $newsIds = [];
        $pageIds = [];
        foreach ($parts as $part) {
            $options = $this->options($part);
            if (($options['source'] ?? null) === 'news' && ($options['mode'] ?? 'latest') === 'specific') {
                $newsIds = [...$newsIds, ...$this->intIds($options['newsIds'] ?? [])];
            }
            if (($options['source'] ?? null) === 'pages' && ($options['mode'] ?? 'latest') === 'specific') {
                $pageIds = [...$pageIds, ...$this->intIds($options['pageIds'] ?? [])];
            }
        }

        $newsById = $this->fetchNews(array_unique($newsIds));
        $pagesById = $this->fetchPages(array_unique($pageIds));

        $resolved = [];
        foreach ($parts as $id => $part) {
            $resolved[$id] = $this->resolvePart($part, $newsById, $pagesById, $context);
        }

        return $resolved;
    }

    /**
     * @param  Collection<int, News>  $newsById
     * @param  Collection<int, Page>  $pagesById
     * @return array<string, mixed>
     */
    private function resolvePart(ContentPart $part, Collection $newsById, Collection $pagesById, ResolutionContext $context): array
    {
        $options = $this->options($part);
        $source = $options['source'] ?? 'manual';
        $mode = $options['mode'] ?? 'latest';
        $limit = max(1, min(self::MAX_ITEMS, (int) ($options['limit'] ?? self::MAX_ITEMS)));

        $items = [];
        $droppedForLocale = 0;

        if ($source === 'manual') {
            $items = $this->resolveManualLinks($part);
        } elseif ($source === 'news') {
            $candidates = $mode === 'specific'
                ? collect($this->intIds($options['newsIds'] ?? []))->map(fn ($id) => $newsById->get($id))->filter()
                : $this->fetchLatestNews($options, $context, $limit);

            foreach ($candidates->take($limit) as $news) {
                $resolvedNews = $this->followLocale($news, $context->locale, fn (News $n) => News::find($n->other_lang_id));
                if (! $resolvedNews) {
                    $droppedForLocale++;

                    continue;
                }

                $items[] = [
                    'id' => $resolvedNews->id,
                    'title' => $resolvedNews->title,
                    'href' => route('news', [
                        'subdomain' => $context->subdomain,
                        'lang' => $resolvedNews->lang,
                        'newsString' => $resolvedNews->lang === 'lt' ? 'naujiena' : 'news',
                        'news' => $resolvedNews->permalink,
                    ]),
                    'imageUrl' => $resolvedNews->getImageUrl(),
                    'publishedAt' => optional($resolvedNews->publish_time)->toIso8601String(),
                ];
            }
        } elseif ($source === 'pages') {
            $ids = array_slice($this->intIds($options['pageIds'] ?? []), 0, self::MAX_PINNED_IDS);
            foreach (collect($ids)->map(fn ($id) => $pagesById->get($id))->filter()->take($limit) as $page) {
                $resolvedPage = $this->followLocale($page, $context->locale, fn (Page $p) => Page::query()->where('is_active', true)->find($p->other_lang_id));
                if (! $resolvedPage) {
                    $droppedForLocale++;

                    continue;
                }

                $items[] = [
                    'id' => $resolvedPage->id,
                    'title' => $resolvedPage->title,
                    'href' => route('page', [
                        'subdomain' => $context->subdomain,
                        'lang' => $resolvedPage->lang,
                        'permalink' => $resolvedPage->permalink,
                    ]),
                    'imageUrl' => null,
                    'publishedAt' => null,
                ];
            }
        }

        return [
            'type' => 'link-list',
            'items' => $items,
            'meta' => [
                'total' => count($items),
                'truncated' => false,
                'droppedForLocale' => $droppedForLocale,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function resolveManualLinks(ContentPart $part): array
    {
        $links = (array) ($part->json_content['links'] ?? []);
        $items = [];

        foreach (array_slice($links, 0, self::MAX_ITEMS) as $link) {
            $url = is_string($link['url'] ?? null) ? $link['url'] : null;
            $title = is_string($link['title'] ?? null) ? trim($link['title']) : null;
            $imageUrl = is_string($link['imageUrl'] ?? null) && $link['imageUrl'] !== '' ? $link['imageUrl'] : null;

            // Never trust an author-typed URL without validating scheme+shape — an
            // href rendered straight from this array must not become `javascript:`.
            if (! $url || $title === '' || $title === null || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                continue;
            }

            $items[] = ['id' => null, 'title' => $title, 'href' => $url, 'imageUrl' => $imageUrl, 'publishedAt' => null];
        }

        return $items;
    }

    /** @return Collection<int, News> */
    private function fetchLatestNews(array $options, ResolutionContext $context, int $limit): Collection
    {
        $query = News::query()
            ->where('lang', $context->locale)
            ->where('draft', false)
            ->where('publish_time', '<=', now());

        $alias = $options['categoryAlias'] ?? null;
        if (is_string($alias) && $alias !== '') {
            $categoryId = Category::query()->where('alias', $alias)->value('id');
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
        }

        $tenantScope = $options['tenantScope'] ?? 'current';
        if ($tenantScope === 'current') {
            $query->where('tenant_id', $context->tenant->id);
        }
        // 'all' → no tenant filter.

        return $query->orderByDesc('publish_time')->limit($limit)->get([
            'id', 'title', 'permalink', 'lang', 'other_lang_id', 'tenant_id', 'publish_time', 'image',
        ]);
    }

    /** @return Collection<int, News> */
    private function fetchNews(array $ids): Collection
    {
        if (! $ids) {
            return collect();
        }

        return News::query()
            ->whereIn('id', array_slice($ids, 0, self::MAX_PINNED_IDS * 4))
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->get(['id', 'title', 'permalink', 'lang', 'other_lang_id', 'tenant_id', 'publish_time', 'image'])
            ->keyBy('id');
    }

    /** @return Collection<int, Page> */
    private function fetchPages(array $ids): Collection
    {
        if (! $ids) {
            return collect();
        }

        return Page::query()
            ->whereIn('id', array_slice($ids, 0, self::MAX_PINNED_IDS * 4))
            ->where('is_active', true)
            ->get(['id', 'title', 'permalink', 'lang', 'other_lang_id', 'tenant_id'])
            ->keyBy('id');
    }

    /**
     * Follows `other_lang_id` when a resolved record's language doesn't match the
     * viewer's — a `link-list` picked while authoring in Lithuanian must not render an
     * LT title to an EN visitor. Drops the item (caller counts it) when there is no
     * counterpart, rather than showing the wrong language.
     *
     * @template T of News|Page
     *
     * @param  T|null  $model
     * @param  callable(T): (T|null)  $findOther
     * @return T|null
     */
    private function followLocale(?object $model, string $locale, callable $findOther): ?object
    {
        if (! $model) {
            return null;
        }
        if ($model->lang === $locale) {
            return $model;
        }

        return $findOther($model);
    }

    /** @return array<string, mixed> */
    private function options(ContentPart $part): array
    {
        return (array) ($part->options ?? []);
    }

    /** @return list<int> */
    private function intIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_slice(array_map('intval', array_filter($value, 'is_numeric')), 0, self::MAX_PINNED_IDS));
    }
}
