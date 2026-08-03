<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Read-only client for the self-hosted Umami analytics API.
 *
 * Only ever used server-side, for the tenant statistics on the Svetainė dashboard. The
 * public tracker is a plain script tag rendered in app.blade.php and does not go through
 * here.
 *
 * Two deliberate behaviours:
 * - Every failure degrades to `null` rather than an exception. Analytics are a nice-to-have
 *   on an admin page; a down or slow Umami must never break the dashboard.
 * - Responses are cached, because these numbers do not need to be real-time and the
 *   dashboard is re-rendered on every tenant switch.
 *
 * API note: this targets Umami **v3**, whose parameters differ from the widely published
 * v2 documentation. Scoping uses `hostname=` (v2's `host=` is silently ignored, returning
 * unfiltered totals) and page breakdowns use `type=path` (v2's `type=url` returns 400).
 */
class UmamiClient
{
    private const string TOKEN_CACHE_KEY = 'umami.api_token';

    /** Well under Umami's own token lifetime, so a cached token is not served past expiry. */
    private const TOKEN_TTL = 60 * 60 * 6;

    private const RESULT_TTL = 60 * 10;

    private const int TIMEOUT = 5;

    /**
     * Whether the API is configured at all. Staging and CI leave it empty.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.umami.api_url'))
            && filled(config('services.umami.website_id'))
            && filled(config('services.umami.username'));
    }

    /**
     * Totals, a daily series and the most viewed pages for one tenant's hostname.
     *
     * @return array{totals: array<string, int>, series: list<array{date: string, pageviews: int, visitors: int}>, topPages: list<array{path: string, views: int}>}|null
     */
    public function overview(string $hostname, CarbonInterface $start, CarbonInterface $end): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = sprintf(
            'umami.overview.%s.%s.%s',
            $hostname,
            $start->toDateString(),
            $end->toDateString()
        );

        return Cache::remember($cacheKey, self::RESULT_TTL, function () use ($hostname, $start, $end) {
            $range = [
                'startAt' => $start->getTimestampMs(),
                'endAt' => $end->getTimestampMs(),
                'hostname' => $hostname,
            ];

            $stats = $this->get('stats', $range);

            if ($stats === null) {
                return null;
            }

            $series = $this->get('pageviews', $range + [
                'unit' => 'day',
                'timezone' => config('app.timezone', 'UTC'),
            ]) ?? [];

            $topPages = $this->get('metrics', $range + [
                'type' => 'path',
                'limit' => 10,
            ]) ?? [];

            return [
                'totals' => [
                    'pageviews' => (int) ($stats['pageviews'] ?? 0),
                    'visitors' => (int) ($stats['visitors'] ?? 0),
                    'visits' => (int) ($stats['visits'] ?? 0),
                    'bounces' => (int) ($stats['bounces'] ?? 0),
                ],
                'series' => $this->normalizeSeries($series),
                'topPages' => $this->normalizeTopPages($topPages),
            ];
        });
    }

    /**
     * Totals for a single public URL, e.g. one news article.
     *
     * The path is always derived server-side from the record being viewed — never accepted
     * from the client, which would let anyone probe traffic for arbitrary URLs.
     *
     * @return array{pageviews: int, visitors: int, visits: int}|null
     */
    public function pathStats(string $hostname, string $path, CarbonInterface $start, CarbonInterface $end): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = sprintf('umami.path.%s.%s.%s', $hostname, md5($path), $start->toDateString());

        return Cache::remember($cacheKey, self::RESULT_TTL, function () use ($hostname, $path, $start, $end) {
            $stats = $this->get('stats', [
                'startAt' => $start->getTimestampMs(),
                'endAt' => $end->getTimestampMs(),
                'hostname' => $hostname,
                'path' => $path,
            ]);

            if ($stats === null) {
                return null;
            }

            return [
                'pageviews' => (int) ($stats['pageviews'] ?? 0),
                'visitors' => (int) ($stats['visitors'] ?? 0),
                'visits' => (int) ($stats['visits'] ?? 0),
            ];
        });
    }

    /**
     * GET a website-scoped endpoint, re-authenticating once if the cached token expired.
     *
     * @param  array<string, mixed>  $query
     * @return array<array-key, mixed>|null
     */
    private function get(string $endpoint, array $query): ?array
    {
        $response = $this->request($endpoint, $query);

        if ($response?->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->request($endpoint, $query);
        }

        if ($response === null || $response->failed()) {
            Log::warning('Umami request failed', [
                'endpoint' => $endpoint,
                'status' => $response?->status(),
            ]);

            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function request(string $endpoint, array $query): ?Response
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        $url = sprintf(
            '%s/api/websites/%s/%s',
            rtrim((string) config('services.umami.api_url'), '/'),
            config('services.umami.website_id'),
            $endpoint
        );

        try {
            return Http::withToken($token)->timeout(self::TIMEOUT)->get($url, $query);
        } catch (\Throwable $e) {
            Log::warning('Umami request threw', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Bearer token from username/password, cached between requests.
     */
    private function token(): ?string
    {
        $token = Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL, function () {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->post(rtrim((string) config('services.umami.api_url'), '/').'/api/auth/login', [
                        'username' => config('services.umami.username'),
                        'password' => config('services.umami.password'),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Umami login threw', ['message' => $e->getMessage()]);

                return null;
            }

            if ($response->failed()) {
                Log::warning('Umami login failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('token');
        });

        // Never cache a failed login — the next request should try again.
        if (! is_string($token) || $token === '') {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return null;
        }

        return $token;
    }

    /**
     * Umami returns `[{x: '2026-07-01', y: 12}, …]` per metric; flatten pageviews and
     * sessions into one row per day for charting.
     *
     * @param  array<array-key, mixed>  $series
     * @return list<array{date: string, pageviews: int, visitors: int}>
     */
    private function normalizeSeries(array $series): array
    {
        $visitorsByDate = [];

        foreach ($series['sessions'] ?? [] as $point) {
            if (is_array($point) && isset($point['x'])) {
                $visitorsByDate[(string) $point['x']] = (int) ($point['y'] ?? 0);
            }
        }

        $rows = [];

        foreach ($series['pageviews'] ?? [] as $point) {
            if (! is_array($point) || ! isset($point['x'])) {
                continue;
            }

            $date = (string) $point['x'];

            $rows[] = [
                'date' => $date,
                'pageviews' => (int) ($point['y'] ?? 0),
                'visitors' => $visitorsByDate[$date] ?? 0,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<array-key, mixed>  $metrics
     * @return list<array{path: string, views: int}>
     */
    private function normalizeTopPages(array $metrics): array
    {
        $rows = [];

        foreach ($metrics as $metric) {
            if (! is_array($metric) || ! isset($metric['x'])) {
                continue;
            }

            $rows[] = [
                'path' => (string) $metric['x'],
                'views' => (int) ($metric['y'] ?? 0),
            ];
        }

        return $rows;
    }
}
