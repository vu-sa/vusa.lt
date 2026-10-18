<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ActivityRequests\ResolveActivityRequestRecipients;
use App\Actions\ActivityRequests\ResolveAskableInstitutions;
use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Enums\InstitutionActivityCampaign;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ActivityRequests\PreviewInstitutionActivityRequestsRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
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
        ResolveAskableInstitutions $askable,
        InstitutionActivityStatusService $statuses,
    ): JsonResponse {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        $institutions = $this->reachable($request, $askable)
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
    public function people(Request $request, ResolveAskableInstitutions $askable, ResolveActivityRequestRecipients $recipients): JsonResponse
    {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        return $this->jsonSuccess($recipients->people($this->reachable($request, $askable)->get(['id', 'name'])));
    }

    /** @return Builder<Institution> */
    private function reachable(Request $request, ResolveAskableInstitutions $askable): Builder
    {
        /** @var User $user */
        $user = $request->user();

        return $askable->query($user)->where('is_active', true);
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
