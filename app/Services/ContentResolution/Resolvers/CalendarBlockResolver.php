<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Models\Calendar;
use App\Models\ContentPart;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Resolves `calendar` blocks so they stop client-fetching (`useCalendarFetch()`) on
 * ordinary pages.
 *
 * Upcoming events only, soonest first, capped at `options.limit` — same "upcoming"
 * window `EventListResolver` uses. `EventCalendarElement.vue` used to fetch a large
 * (100-row) pool ordered newest-first and re-derive "the next N upcoming events"
 * client-side (filter, sort, slice); with an author-configurable `limit` that pool
 * ordering is wrong — `orderByDesc('date')` picks the *furthest*-future (or most
 * recent past) rows first, which are frequently not the *soonest* upcoming ones once
 * the row count is small. Doing the real query server-side is both correct and lets
 * `EventCalendarElement.vue` render the result directly.
 *
 * `options.tenantScope` picks which tenants' events to show — a specific list, or
 * `'all'` (default; matches the historical "recent activity across all of VU SA"
 * homepage-widget behavior every existing saved block already relies on). An empty
 * array is a deliberate "none selected" author choice, not "no filter" — unlike
 * `EventListResolver`'s array case, which falls through to unfiltered when empty
 * (there, empty only ever happens via a stray/malformed request, never an author
 * "select none" action; here, `RCTenantMultiSelect.vue`'s "None" button produces
 * exactly this state, and it must show zero events, not silently every tenant's).
 *
 * `options.categoryAlias` lets an author narrow a specific block beyond that default —
 * each block resolves independently (no shared/batched query) since, like
 * `EventListResolver`, two calendar blocks on the same page can carry different
 * limits/categories/tenant scopes.
 */
final class CalendarBlockResolver implements ResolvesContentPart
{
    private const int DEFAULT_LIMIT = 3;

    private const int MAX_LIMIT = 10;

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
        $alias = $options['categoryAlias'] ?? null;

        $query = Calendar::query()->with(['category', 'media'])
            ->published()->forLocale($context->locale)
            ->inCategoryAlias(is_string($alias) && $alias !== '' ? $alias : null)
            // Same rolling "upcoming" window as EventListResolver's default mode — a
            // day of grace behind now so an event still in progress isn't dropped.
            ->where('date', '>=', now()->subDay());

        $tenantScope = $options['tenantScope'] ?? 'all';
        if (is_array($tenantScope)) {
            $ids = array_slice(array_map(intval(...), array_filter($tenantScope, is_numeric(...))), 0, self::MAX_TENANT_IDS);
            // Unconditional, even when empty: `whereIn('tenant_id', [])` correctly
            // matches zero rows — the "None" button's explicit choice, not a bug.
            $query->whereIn('tenant_id', $ids);
        } elseif ($tenantScope === 'current') {
            $query->where('tenant_id', $context->tenant->id);
        }
        // 'all' → no tenant filter.

        $items = $query->orderBy('date')->take($limit)->get()->map(fn (Calendar $event) => [
            ...$event->toArray(),
            'images' => $event->getMedia('images'),
            'googleLink' => $event->googleLink(),
        ])->all();

        return [
            'type' => 'calendar',
            'items' => $items,
            'meta' => ['total' => count($items)],
        ];
    }
}
