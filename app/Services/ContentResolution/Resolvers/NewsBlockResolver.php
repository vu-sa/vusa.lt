<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Collections\NewsCollection;
use App\Models\ContentPart;
use App\Models\News;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Resolves each news block independently so its category, tag, tenant and count options
 * are applied before the public component receives its article cards.
 */
final class NewsBlockResolver implements ResolvesContentPart
{
    private const int DEFAULT_LIMIT = 4;

    private const int MAX_LIMIT = 4;

    private const int MAX_TENANT_IDS = 20;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        $resolved = [];
        foreach ($parts as $id => $part) {
            $resolved[$id] = $this->resolvePart($part, $context);
        }

        return $resolved;
    }

    /** @return array<string, mixed> */
    private function resolvePart(ContentPart $part, ResolutionContext $context): array
    {
        $options = (array) ($part->options ?? []);
        $limit = max(1, min(self::MAX_LIMIT, (int) ($options['limit'] ?? self::DEFAULT_LIMIT)));
        $categoryAlias = $options['categoryAlias'] ?? null;
        $tagAlias = $options['tagAlias'] ?? null;

        $query = News::query()
            ->where('lang', $context->locale)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->with('category:id,name')
            ->orderByDesc('publish_time');

        if (is_string($categoryAlias) && $categoryAlias !== '') {
            $query->whereHas('category', fn ($category) => $category->where('alias', $categoryAlias));
        }

        if (is_string($tagAlias) && $tagAlias !== '') {
            $query->whereHas('tags', fn ($tag) => $tag->where('alias', $tagAlias));
        }

        $tenantScope = $options['tenantScope'] ?? 'current';
        if (is_array($tenantScope)) {
            $ids = array_slice(array_map(intval(...), array_filter($tenantScope, is_numeric(...))), 0, self::MAX_TENANT_IDS);
            $query->whereIn('tenant_id', $ids);
        } elseif ($tenantScope === 'current') {
            $query->where('tenant_id', $context->tenant->id);
        }

        $news = $query->take($limit)
            ->get(['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image', 'category_id', 'other_lang_id', 'tenant_id'])
            ->all();
        $items = new NewsCollection($news)->toPublicArray();

        return [
            'type' => 'news',
            'items' => $items,
            'meta' => ['total' => count($items)],
        ];
    }
}
