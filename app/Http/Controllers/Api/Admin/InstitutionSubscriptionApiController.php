<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\BulkInstitutionFollowRequest;
use App\Models\Institution;
use App\Services\InstitutionSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionSubscriptionApiController extends ApiController
{
    public function __construct(
        protected InstitutionSubscriptionService $subscriptionService,
    ) {}

    /**
     * Get subscription status for a specific institution.
     */
    public function status(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);
        $this->authorizeApi('viewSummary', $institution);

        return $this->jsonSuccess(
            $this->subscriptionService->getStatus($user, $institution)
        );
    }

    /**
     * Follow an institution.
     */
    public function follow(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);
        $this->authorizeApi('follow', $institution);

        $this->subscriptionService->follow($user, $institution);

        return $this->jsonSuccess([
            'is_followed' => true,
            'message' => __('visak.institution_followed'),
        ]);
    }

    /**
     * Unfollow an institution.
     */
    public function unfollow(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);

        // No authorization needed - users can always unfollow what they followed
        $this->subscriptionService->unfollow($user, $institution);

        return $this->jsonSuccess([
            'is_followed' => false,
            'is_muted' => false,
            'message' => __('visak.institution_unfollowed'),
        ]);
    }

    /**
     * Follow several institutions; refused as a whole when any one may not be followed
     * (InstitutionPolicy::follow).
     */
    public function followMany(BulkInstitutionFollowRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $institutions = Institution::query()->whereIn('id', $request->institutionIds())->get();

        foreach ($institutions as $institution) {
            $this->authorizeApi('follow', $institution);
        }

        $this->subscriptionService->followMany($user, $institutions);

        return $this->jsonSuccess([
            'institution_ids' => $institutions->pluck('id')->map(fn ($id): string => (string) $id)->values(),
            'is_followed' => true,
        ]);
    }

    /**
     * Unfollow several institutions; only the user's own follows are touched.
     */
    public function unfollowMany(BulkInstitutionFollowRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $institutionIds = $request->institutionIds();

        $this->subscriptionService->unfollowMany($user, $institutionIds);

        return $this->jsonSuccess([
            'institution_ids' => $institutionIds,
            'is_followed' => false,
        ]);
    }

    /**
     * Mute notifications for an institution.
     */
    public function mute(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);
        $this->authorizeApi('follow', $institution);

        $this->subscriptionService->mute($user, $institution);

        return $this->jsonSuccess([
            'is_muted' => true,
            'message' => __('visak.notifications_muted'),
        ]);
    }

    /**
     * Unmute notifications for an institution.
     */
    public function unmute(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);

        $this->subscriptionService->unmute($user, $institution);

        return $this->jsonSuccess([
            'is_muted' => false,
            'message' => __('visak.notifications_unmuted'),
        ]);
    }

    /**
     * Reset subscription preferences to defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $clearFollows = $request->boolean('clear_follows', false);

        $this->subscriptionService->resetToDefaults($user, $clearFollows);

        return $this->jsonSuccess([
            'message' => __('visak.preferences_reset'),
        ]);
    }
}
