<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Collections\NewsCollection;
use App\Models\ContentPart;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Bridges the legacy `news` block onto the resolver so it stops client-fetching
 * (`useNewsFetch()`) on ordinary pages — `HomePage.vue` already prefetches this exact
 * shape server-side; every other page rendering a `news` block previously fired an
 * API waterfall with no prefetch at all. Byte-identical payload to
 * `NewsCollection::getPublishedForTenant()->toPublicArray()`, the same call
 * `PublicPageController::home()` already makes, so `NewsElement.vue`'s existing
 * `prefetchedNews`-shaped consumption needs no changes.
 */
final class NewsBlockResolver implements ResolvesContentPart
{
    private const int LIMIT = 5;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        // Every `news` block on a page renders the same "latest for this tenant" feed
        // (the type has no per-block filtering options) — resolve it once and reuse.
        $items = NewsCollection::getPublishedForTenant($context->tenant->id, $context->locale, self::LIMIT)->toPublicArray();

        $payload = [
            'type' => 'news',
            'items' => $items,
            'meta' => ['total' => count($items)],
        ];

        return collect($parts)->map(fn (ContentPart $part) => $payload)->all();
    }
}
