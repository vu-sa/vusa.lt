<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AtstovavimasMeetingsRequest;
use App\Http\Requests\Api\Admin\AtstovavimasRepresentativesRequest;
use App\Http\Requests\Api\Admin\AtstovavimasTenantRequest;
use App\Models\User;
use App\Services\AtstovavimasDashboardService;
use App\Settings\AtstovavimasSettings;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AtstovavimasApiController extends ApiController
{
    /**
     * Tenant timeline data is identical for every user authorized to see the same
     * tenant set, so it is cached briefly; mutations bypass via ?refresh=1.
     */
    private const int CACHE_TTL_SECONDS = 600;

    public function __construct(
        private readonly AtstovavimasDashboardService $dashboardService,
        private readonly AtstovavimasSettings $settings,
    ) {}

    public function timeline(AtstovavimasTenantRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);

        return $this->jsonSuccess(
            $this->rememberVisak('timeline', $tenantIds, $request->boolean('refresh'), fn () => $this->dashboardService->tenantTimeline($tenantIds->all()))
        );
    }

    public function meetings(AtstovavimasMeetingsRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);
        $from = Carbon::parse($request->validated('from'))->startOfDay();
        $until = Carbon::parse($request->validated('until'))->endOfDay();

        return $this->jsonSuccess(
            $this->rememberVisak(
                'meetings',
                $tenantIds,
                $request->boolean('refresh'),
                fn () => $this->dashboardService->tenantMeetings($tenantIds->all(), $from, $until),
                $from->toDateString().':'.$until->toDateString(),
            )
        );
    }

    public function representatives(AtstovavimasRepresentativesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);
        $perPage = (int) $request->validated('per_page', 20);

        return $this->jsonSuccess($this->dashboardService->representativeUsers(
            $tenantIds->all(),
            $request->validated('category', 'all'),
            $request->validated('search'),
            $perPage,
        ));
    }

    /**
     * @param  Collection<int, int>  $tenantIds
     */
    private function rememberVisak(string $kind, Collection $tenantIds, bool $bypassCache, Closure $callback, string $suffix = ''): mixed
    {
        $key = 'visak:'.$kind.':'.md5($tenantIds->sort()->implode(',').':'.$suffix);

        if ($bypassCache) {
            Cache::forget($key);
        }

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        // An empty result is usually a transient state (e.g. no meetings yet in a
        // freshly-created window) — not worth pinning for the full TTL, since it
        // would otherwise mask genuinely new data for that long.
        if ($value !== [] && $value !== null) {
            Cache::put($key, $value, self::CACHE_TTL_SECONDS);
        }

        return $value;
    }

    /**
     * @param  array<int, int|string>  $requestedTenantIds
     * @return Collection<int, int>
     */
    private function authorizedTenantIds(array $requestedTenantIds, User $user): Collection
    {
        $tenantIds = collect($requestedTenantIds)
            ->map(fn ($tenantId) => (int) $tenantId)
            ->unique()
            ->values();
        $visibleTenantIds = $this->settings->getVisibleTenantIds($user)
            ->map(fn ($tenantId) => (int) $tenantId);

        abort_if($tenantIds->diff($visibleTenantIds)->isNotEmpty(), 403);

        return $tenantIds;
    }
}
