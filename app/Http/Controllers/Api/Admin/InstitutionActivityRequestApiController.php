<?php

namespace App\Http\Controllers\Api\Admin;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the "Paklausti, ar vyko posėdžiai" action window flow: which institutions the caller may
 * ask about, and who would be emailed for each before they send.
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
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAny', InstitutionCheckIn::class);

        $scope = $authorizer->scope($user, 'institutions.update.padalinys');
        $coordinatedIds = $responsibilities->institutionIdsFor($user, Responsibility::StudentRepCoordination);

        $institutions = Institution::query()
            ->where('is_active', true)
            ->when(! $scope->isAllScope, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereIn('tenant_id', $scope->tenantIds())
                ->orWhereIn('id', $coordinatedIds)))
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
     * @route POST /api/v1/admin/activity-requests/preview
     */
    public function preview(PreviewInstitutionActivityRequestsRequest $request, SendInstitutionActivityRequests $send): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->jsonSuccess(array_map(fn (array $entry): array => [
            'institution' => ['id' => (string) $entry['institution']->id, 'name' => is_string($entry['institution']->name) ? $entry['institution']->name : ''],
            'recipients' => array_values($entry['recipient_periods']),
            'excluded_recipients' => $entry['excluded_recipients'],
            'recipient_count' => $entry['recipients']->count(),
            'campaign_type' => $entry['campaign_type']->value,
            'period_end' => $entry['period_end']->toDateString(),
            'period_start' => $entry['period_start']->toDateString(),
            'skip_reason' => $entry['skip_reason'],
        ], $send->plan($request->institutions(), $user, InstitutionActivityCampaign::from($request->validated('campaign_type')))));
    }
}
