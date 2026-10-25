<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepStatisticsCache
{
    private const string GLOBAL_REVISION = 'visak:live-revision';

    private const string ANY_TENANT_REVISION = 'visak:rev:any';

    private const string SNAPSHOT_REVISION = 'visak:snapshot-revision';

    /**
     * @param  list<int|string>|null  $tenantIds  the tenants the change touched; null when it cannot be narrowed
     */
    public static function invalidate(bool $history = false, ?array $tenantIds = null): void
    {
        DB::afterCommit(static function () use ($history, $tenantIds): void {
            if ($tenantIds === null) {
                Cache::forever(self::GLOBAL_REVISION, Str::uuid()->toString());
            } else {
                foreach (array_unique($tenantIds) as $tenantId) {
                    Cache::forever(self::tenantRevision($tenantId), Str::uuid()->toString());
                }
                Cache::forever(self::ANY_TENANT_REVISION, Str::uuid()->toString());
            }

            if ($history) {
                Cache::forever(self::SNAPSHOT_REVISION, Str::uuid()->toString());
            }
        });
    }

    /**
     * @param  list<int|string>|null  $tenantIds  what the payload depends on; null depends on every tenant
     */
    public static function remember(string $scope, Closure $compute, bool $history = false, bool $refresh = false, ?array $tenantIds = null, ?int $ttl = null): mixed
    {
        // Payloads carry translated names and labels, so a locale never shares an entry with another.
        $key = 'visak:statistics:v7:'.md5($scope.':'.app()->getLocale().':'.today()->toDateString().':'.self::revision($history, $tenantIds));

        // One forced rebuild per key every few seconds: the revision already follows every write,
        // so ?refresh=1 must not let a caller keep evicting an entry everyone shares.
        $rebuild = $refresh && Cache::add($key.':refreshed', true, 10);

        if (! $rebuild && ($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        return Cache::lock($key.':lock', 60)->block(10, function () use ($key, $compute, $history, $ttl, $rebuild) {
            if ($rebuild) {
                Cache::forget($key);
            }

            return Cache::remember($key, $ttl ?? ($history ? 86400 : 600), $compute);
        });
    }

    /** @param list<int|string>|null $tenantIds */
    private static function revision(bool $history, ?array $tenantIds): string
    {
        if ($history) {
            return (string) Cache::get(self::SNAPSHOT_REVISION, 'initial');
        }

        if ($tenantIds !== null) {
            sort($tenantIds);
        }
        $keys = [self::GLOBAL_REVISION, ...($tenantIds === null ? [self::ANY_TENANT_REVISION] : array_map(self::tenantRevision(...), $tenantIds))];

        return md5(implode('|', array_map(fn ($revision): string => (string) $revision, Cache::many($keys))));
    }

    private static function tenantRevision(int|string $tenantId): string
    {
        return 'visak:rev:tenant:'.$tenantId;
    }
}
