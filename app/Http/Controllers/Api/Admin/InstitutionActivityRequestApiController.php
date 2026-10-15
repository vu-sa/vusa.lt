<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ActivityRequests\ResolveActivityRequestRecipients;
use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\Responsibility;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ActivityRequests\PreviewInstitutionActivityRequestsRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use App\Services\ModelAuthorizer;
use App\Services\ResponsibilityResolver;
use App\Support\ActivityRequests\ActivityRequestPlanEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the "Paklausti, ar vyko posėdžiai" action window flow: which institutions and people the
 * caller may ask, and who would be emailed for each before they send.
 */
class InstitutionActivityRequestApiController extends ApiController
{
    /**
     * @route GET /api/v1/admin/activity-requests/candidates
     */
    public function candidates(
        Request $request,
        ModelAuthorizer $authorizer,
        ResponsibilityResolver $responsibilities,
        InstitutionActivityStatusService $statuses,
    ): JsonResponse {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        $institutions = $this->reachable($request, $authorizer, $responsibilities)
            ->with(['types', 'tenant', 'meetings:id,start_time', 'checkIns'])
            ->get();

        $payload = $institutions
            ->map(fn (Institution $institution): array => [
                'id' => (string) $institution->id,
                'name' => is_string($institution->name) ? $institution->name : '',
                'tenant_id' => $institution->tenant_id,
                'tenant_shortname' => $institution->tenant?->shortname,
                'activity_status' => $statuses->resolve($institution)->toArray(),
            ])
            ->sortBy([
                fn (array $a, array $b) => $b['activity_status']['requires_action'] <=> $a['activity_status']['requires_action'],
                fn (array $a, array $b) => $b['activity_status']['priority'] <=> $a['activity_status']['priority'],
                fn (array $a, array $b) => strcasecmp($a['name'], $b['name']),
            ])
            ->values()
            ->all();

        return $this->jsonSuccess($payload);
    }

    /**
     * @route GET /api/v1/admin/activity-requests/people
     */
    public function people(Request $request, ModelAuthorizer $authorizer, ResponsibilityResolver $responsibilities, ResolveActivityRequestRecipients $recipients): JsonResponse
    {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        return $this->jsonSuccess($recipients->people($this->reachable($request, $authorizer, $responsibilities)->get(['id', 'name'])));
    }

    /** @return Builder<Institution> */
    private function reachable(Request $request, ModelAuthorizer $authorizer, ResponsibilityResolver $responsibilities): Builder
    {
        /** @var User $user */
        $user = $request->user();
        $scope = $authorizer->scope($user, 'institutions.update.padalinys');
        $coordinatedIds = $responsibilities->institutionIdsFor($user, Responsibility::StudentRepCoordination);

        return Institution::query()
            ->where('is_active', true)
            ->when(! $scope->isAllScope, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereIn('tenant_id', $scope->tenantIds())
                ->orWhereIn('id', $coordinatedIds)));
    }

    /**
     * @route POST /api/v1/admin/activity-requests/preview
     */
    public function preview(PreviewInstitutionActivityRequestsRequest $request, SendInstitutionActivityRequests $send): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $plan = $send->plan($request->institutions(), $user, InstitutionActivityCampaign::from($request->validated('campaign_type')), $request->pairs());

        return $this->jsonSuccess(array_map(fn (ActivityRequestPlanEntry $entry): array => $entry->toPreviewArray(), $plan));
    }
}
