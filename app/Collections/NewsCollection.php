<?php

namespace App\Collections;

use App\Models\News;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Custom collection for News models with transformation methods
 *
 * @extends Collection<int, News>
 */
class NewsCollection extends Collection
{
    /**
     * Transform news items to a public-facing format with resolved image URLs.
     * Used for API responses and Inertia props.
     *
     * @return array<int, array{
     *     id: int,
     *     title: string,
     *     lang: string,
     *     short: string,
     *     publish_time: Carbon|null,
     *     permalink: string|null,
     *     image: string|null
     * }>
     */
    public function toPublicArray(): array
    {
        return $this->map(fn (News $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'lang' => $item->lang,
            'short' => $item->short,
            'publish_time' => $item->publish_time,
            'permalink' => $item->permalink,
            'image' => $item->getImageUrl(),
        ])->values()->all();
    }

    /**
     * Get the first news item's image URL for LCP preloading.
     */
    public function getFirstImageUrl(): ?string
    {
        $first = $this->first();

        return $first?->getImageUrl();
    }

    /**
     * Scope to get published news only. The canonical "latest published news" query —
     * `NewsBlockResolver` and `LinkListResolver` (source: news) both resolve through this
     * instead of hand-rolling their own copy, so the homepage's prefetch and every
     * content-part rendering of "latest news" can never drift apart.
     *
     * @param  int|null  $tenantId  The tenant ID to filter by, or null for every tenant
     * @param  string  $lang  The language to filter by
     * @param  int  $limit  Maximum number of items to return
     * @param  string|null  $topicAlias  Restrict to one topic tag, by alias
     */
    public static function getPublishedForTenant(?int $tenantId, string $lang, int $limit = 5, ?string $topicAlias = null): self
    {
        $query = News::query()
            ->where('lang', $lang)
            ->where('draft', false)
            ->where('publish_time', '<=', now());

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($topicAlias !== null && $topicAlias !== '') {
            $query->whereHas('tags', fn ($tag) => $tag->where('alias', $topicAlias));
        }

        $news = $query->orderByDesc('publish_time')
            ->take($limit)
            ->get(['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image', 'other_lang_id', 'tenant_id']);

        return new self($news->all());
    }
}
