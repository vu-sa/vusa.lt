<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Models\Calendar;
use App\Models\ContentPart;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Bridges the legacy `calendar` block onto the resolver so it stops client-fetching
 * (`useCalendarFetch()`) on ordinary pages. Reproduces
 * `PublicPageController::getEventsForCalendar()` exactly (same query, same shape) since
 * `EventCalendarElement.vue` already knows how to consume that payload as
 * `prefetchedCalendar` — deliberately *not* tenant-scoped, matching the existing
 * homepage widget's "recent activity across all of VU SA" behavior; `options.allTenants`
 * only affects the *client* fetch that continues past this initial page (see
 * `useCalendarFetch`'s `skipInitialFetch` + `initializeWithData`).
 */
final class CalendarBlockResolver implements ResolvesContentPart
{
    private const LIMIT = 100;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        $query = Calendar::query()->with(['category', 'media'])->where('is_draft', false);

        if ($context->locale === 'en') {
            $query->where('is_international', true);
        }

        $items = $query->orderByDesc('date')->take(self::LIMIT)->get()->map(fn (Calendar $event) => [
            ...$event->toArray(),
            'images' => $event->getMedia('images'),
            'googleLink' => $event->googleLink(),
        ])->all();

        $payload = [
            'type' => 'calendar',
            'items' => $items,
            'meta' => ['total' => count($items)],
        ];

        return collect($parts)->map(fn (ContentPart $part) => $payload)->all();
    }
}
