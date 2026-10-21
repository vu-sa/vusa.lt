<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ActivityRequests\GetOpenActivityRequestSpans;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AtstovavimasMeetingsRequest;
use App\Http\Requests\Api\Admin\AtstovavimasRepresentativesRequest;
use App\Http\Requests\Api\Admin\AtstovavimasStatusHistoryRequest;
use App\Http\Requests\Api\Admin\AtstovavimasTenantRequest;
use App\Models\User;
use App\Services\AtstovavimasDashboardService;
use App\Services\InstitutionRelationService;
use App\Services\RepMetricReportService;
use App\Services\RepStatisticsCache;
use App\Settings\AtstovavimasSettings;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AtstovavimasApiController extends ApiController
{
    public function __construct(
        private readonly AtstovavimasDashboardService $dashboardService,
        private readonly AtstovavimasSettings $settings,
    ) {}

    public function timeline(AtstovavimasTenantRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);

        return $this->jsonSuccess(
            $this->rememberVisak('timeline', $tenantIds->sort()->implode(','), $request->boolean('refresh'), fn () => $this->dashboardService->tenantTimeline($tenantIds->all()), 'activity-status-v4', $tenantIds->all())
        );
    }

    /**
     * The padaliniai Gantt rows: open to every admin, trimmed per row to what they may read.
     */
    public function gantt(AtstovavimasTenantRequest $request): JsonResponse
    {
        $access = $this->ganttAccess($this->requireAuth($request), $request);

        return $this->jsonSuccess(
            $this->rememberVisak('gantt', $this->accessScope($access), $request->boolean('refresh'), fn () => $this->dashboardService->ganttInstitutions($access), 'activity-dates-v3', $request->tenantIds())
        );
    }

    /**
     * Open activity requests over the Gantt's rows. Per user (askers see all, others their own), so never cached.
     */
    public function activityRequests(AtstovavimasTenantRequest $request, GetOpenActivityRequestSpans $spans): JsonResponse
    {
        $user = $this->requireAuth($request);
        $access = $this->ganttAccess($user, $request);

        return $this->jsonSuccess($spans->execute($user, $access->keys()));
    }

    public function statusHistory(AtstovavimasStatusHistoryRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);
        $days = (int) $request->validated('days', 90);

        return $this->jsonSuccess($this->dashboardService->tenantStatusHistory($tenantIds->sort()->values()->all(), $days, $request->boolean('refresh')));
    }

    public function repMetrics(AtstovavimasTenantRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $tenantIds = $this->authorizedTenantIds($request->validated('tenant_ids'), $user);

        return $this->jsonSuccess(
            $this->rememberVisak(
                'rep_metrics',
                $tenantIds->sort()->implode(','),
                $request->boolean('refresh'),
                fn () => $this->repMetricsFor($tenantIds->map(fn ($id): int => (int) $id)->values()->all()),
                'cadence-outcomes-v3',
                $tenantIds->all(),
            )
        );
    }

    public function meetings(AtstovavimasMeetingsRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $access = $this->ganttAccess($user, $request);
        $from = Carbon::parse($request->validated('from'))->startOfDay();
        $until = Carbon::parse($request->validated('until'))->endOfDay();

        return $this->jsonSuccess(
            $this->rememberVisak(
                'meetings',
                // Private agenda items are redacted per reader (past members, scoped permissions), which the access map does not capture.
                $this->accessScope($access).'|user:'.$user->id,
                $request->boolean('refresh'),
                fn () => $this->dashboardService->ganttMeetings($access, $from, $until),
                $from->toDateString().':'.$until->toDateString(),
                $request->tenantIds(),
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
     * Resolved for every Gantt request (rows, requests, each meetings window), so it is kept briefly per user.
     *
     * @return Collection<string, string>
     */
    private function ganttAccess(User $user, AtstovavimasTenantRequest|AtstovavimasMeetingsRequest $request): Collection
    {
        $tenantIds = $request->tenantIds();

        return RepStatisticsCache::remember(
            'gantt-access:'.$user->id.':'.InstitutionRelationService::version().':'.implode(',', collect($tenantIds)->sort()->all()),
            fn () => $this->dashboardService->ganttInstitutionAccess($user, $tenantIds),
            refresh: $request->boolean('refresh'),
            tenantIds: $tenantIds,
            ttl: 120,
        );
    }

    private function repMetricsFor(array $tenantIds): array
    {
        return app(RepMetricReportService::class)->report($tenantIds);
    }

    /**
     * @param  string  $scope  what the payload depends on: a tenant set, or a Gantt access map
     * @param  list<int|string>  $tenantIds  the padaliniai whose changes invalidate the entry
     */
    private function rememberVisak(string $kind, string $scope, bool $bypassCache, Closure $callback, string $suffix, array $tenantIds): mixed
    {
        return RepStatisticsCache::remember($kind.':'.$scope.':'.$suffix, $callback, refresh: $bypassCache, tenantIds: $tenantIds);
    }

    /**
     * Keyed by the resolved rows and their projections, so users who may read the same rows share
     * an entry and one user's full rows never serve another's public ones.
     *
     * @param  Collection<string, string>  $access
     */
    private function accessScope(Collection $access): string
    {
        return $access->sortKeys()->map(fn (string $projection, string $institutionId) => $institutionId.'='.$projection)->implode(',');
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
