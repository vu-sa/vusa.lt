<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ActivityRequests\GetOpenActivityRequestSpans;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AtstovavimasGanttMembersRequest;
use App\Http\Requests\Api\Admin\AtstovavimasGanttRequest;
use App\Http\Requests\Api\Admin\AtstovavimasMeetingAgendaRequest;
use App\Http\Requests\Api\Admin\AtstovavimasMeetingsRequest;
use App\Http\Requests\Api\Admin\AtstovavimasStatusHistoryRequest;
use App\Http\Requests\Api\Admin\AtstovavimasTenantRequest;
use App\Http\Requests\Api\Admin\RepresentativeMeetingsRequest;
use App\Models\User;
use App\Services\AtstovavimasDashboardService;
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
            $this->rememberVisak('timeline', $tenantIds->sort()->implode(','), $request->boolean('refresh'), fn () => $this->dashboardService->tenantTimeline($user, $tenantIds->all()), 'activity-status-v5', $tenantIds->all())
        );
    }

    /**
     * The padaliniai Gantt rows: open to every admin, trimmed per row to what they may read.
     */
    public function gantt(AtstovavimasGanttRequest $request): JsonResponse
    {
        [$access, $scope] = $this->ganttSelection($this->requireAuth($request), $request);

        return $this->jsonSuccess(
            $this->rememberVisak('gantt', $scope, $request->boolean('refresh'), fn () => $this->dashboardService->ganttInstitutions($access), 'rows-shell-v1', $request->tenantIds())
        );
    }

    public function ganttMembers(AtstovavimasGanttMembersRequest $request): JsonResponse
    {
        [$access, $scope] = $this->ganttSelection($this->requireAuth($request), $request);

        return $this->jsonSuccess(
            $this->rememberVisak('gantt-members', $scope, $request->boolean('refresh'), fn () => $this->dashboardService->ganttMembers($access), 'member-history-v1', $request->tenantIds())
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
        [$access, $scope] = $this->ganttSelection($user, $request);
        $from = Carbon::parse($request->validated('from'))->startOfDay();
        $until = Carbon::parse($request->validated('until'))->endOfDay();

        return $this->jsonSuccess(
            $this->rememberVisak(
                'meeting-markers',
                // Outcome summaries still depend on which private items the reader can see.
                $scope.'|user:'.$user->id,
                $request->boolean('refresh'),
                fn () => $this->dashboardService->ganttMeetings($user, $access, $from, $until),
                $from->toDateString().':'.$until->toDateString(),
                $request->tenantIds(),
            )
        );
    }

    // Authorized in RepresentativeMeetingsRequest: each institution must be on the user's dashboard.
    public function representativeMeetings(RepresentativeMeetingsRequest $request): JsonResponse
    {
        return $this->jsonSuccess($this->dashboardService->ganttMeetings(
            $this->requireAuth($request),
            $request->institutionAccess(),
            Carbon::parse($request->validated('from'))->startOfDay(),
            Carbon::parse($request->validated('to'))->endOfDay(),
            includeRestrictedFiles: true,
        ));
    }

    // Authorized in AtstovavimasMeetingAgendaRequest against the selected Gantt's institution access.
    public function meetingAgenda(AtstovavimasMeetingAgendaRequest $request, string $meeting): JsonResponse
    {
        $access = $request->institutionAccess();

        return $this->jsonSuccess($this->dashboardService->ganttMeetingAgenda($this->requireAuth($request), $access['id'], $meeting, $access['projection']));
    }

    /**
     * Resolved for every Gantt request (rows, requests, each meetings window), so it is kept briefly per user.
     *
     * @return Collection<string, string>
     */
    private function ganttAccess(User $user, AtstovavimasTenantRequest $request): Collection
    {
        return $this->dashboardService->cachedGanttInstitutionAccess($user, $request->tenantIds(), $request->boolean('refresh'));
    }

    /** @return array{Collection<string, string>, string} */
    private function ganttSelection(User $user, AtstovavimasGanttRequest $request): array
    {
        $access = $this->ganttAccess($user, $request);
        $ids = $request->institutionIds();
        if ($ids === null) {
            return [$access, $this->accessScope($access)];
        }

        $selected = array_fill_keys($ids, true);
        $access = $access->filter(fn (string $projection, string $id): bool => isset($selected[strtoupper($id)]));

        return [$access, $this->accessScope($access).'|subset:'.implode(',', $ids)];
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
