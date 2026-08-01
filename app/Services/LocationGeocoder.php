<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the free-text `location` of a calendar event to coordinates, so the public
 * event page can render a map.
 *
 * Calendar locations are typed by editors ("Vilnius, Universiteto g. 3", "Trakų r.,
 * Aukštadvario stovyklavietė") and there are no coordinate columns, so this leans on
 * OpenStreetMap's Nominatim.
 *
 * Two deliberate behaviours:
 * - Every failure degrades to `null`. A map is decoration; a slow or down geocoder must
 *   never break an event page, which is why the timeout is short and exceptions are
 *   swallowed.
 * - Both hits and misses are cached. Nominatim's usage policy caps callers at ~1 req/s,
 *   and without a negative cache an unresolvable location would hit the API on every
 *   single page view.
 */
class LocationGeocoder
{
    private const string ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    private const int TIMEOUT = 3;

    /** Places do not move; a successful lookup is good for a long time. */
    private const HIT_TTL = 60 * 60 * 24 * 30;

    /** Short enough that a typo fixed in the admin shows a map the next day. */
    private const MISS_TTL = 60 * 60 * 24;

    /**
     * Coordinates for a location string, or null when it is empty or unresolvable.
     *
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function coordinates(?string $location): ?array
    {
        $query = trim((string) $location);

        if ($query === '') {
            return null;
        }

        $cacheKey = 'geocode:'.md5(mb_strtolower($query));

        // Misses are stored as `false`, not `null`: the cache cannot distinguish a stored
        // null from a missing key, which would defeat the negative cache entirely.
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $result = $this->lookup($query);

        Cache::put($cacheKey, $result ?? false, $result === null ? self::MISS_TTL : self::HIT_TTL);

        return $result;
    }

    /**
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    private function lookup(string $query): ?array
    {
        $place = $this->request($query, 1)[0] ?? null;

        if (! is_array($place) || ! isset($place['lat'], $place['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $place['lat'],
            'lng' => (float) $place['lon'],
            'display_name' => (string) ($place['display_name'] ?? $query),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function request(string $query, int $limit): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                // Nominatim's usage policy requires an identifying User-Agent.
                ->withHeaders(['User-Agent' => config('app.name').' ('.config('app.url').')'])
                ->get(self::ENDPOINT, [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => $limit,
                    'countrycodes' => 'lt',
                    'accept-language' => app()->getLocale(),
                ]);

            if ($response->failed()) {
                return [];
            }

            $places = $response->json();
        } catch (\Throwable $exception) {
            Log::info('Geocoding failed', ['query' => $query, 'message' => $exception->getMessage()]);

            return [];
        }

        return is_array($places) ? $places : [];
    }
}
