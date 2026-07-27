<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\AtstovavimasRepresentativesRequest;
use App\Http\Requests\Api\Admin\AtstovavimasTenantRequest;
use App\Models\User;
use App\Services\AtstovavimasDashboardService;
use App\Settings\AtstovavimasSettings;
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
            $this->dashboardService->tenantTimeline($tenantIds->all())
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
