<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Institution;
use App\Services\InstitutionSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionSubscriptionApiController extends ApiController
{
    public function __construct(
        protected InstitutionSubscriptionService $subscriptionService
    ) {}

    /**
     * Get list of followed institutions for the current user.
     */
    public function followed(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $institutions = $user->followedInstitutions()
            ->with(['types', 'meetings' => function ($query) {
                $query->where('start_time', '>=', now())
                    ->orderBy('start_time', 'asc')
                    ->limit(1);
            }])
            ->get()
            ->map(fn ($institution) => $this->transformInstitution($institution, $user));

        return $this->jsonSuccess($institutions);
    }

    /**
     * Get subscription status for a specific institution.
     */
    public function status(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);
        $this->authorizeApi('view', $institution);

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
        $this->authorizeApi('view', $institution);

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
     * Mute notifications for an institution.
     */
    public function mute(Request $request, Institution $institution): JsonResponse
    {
        $user = $this->requireAuth($request);
        $this->authorizeApi('view', $institution);

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

    /**
     * Transform institution for API response.
     */
    protected function transformInstitution(Institution $institution, $user): array
    {
        $nextMeeting = $institution->meetings->first();

        return [
            'id' => $institution->id,
            'name' => $institution->name,
            'short_name' => $institution->short_name,
            'alias' => $institution->alias,
            'meeting_periodicity_days' => $institution->meeting_periodicity_days,
            'next_meeting' => $nextMeeting ? [
                'id' => $nextMeeting->id,
                'title' => $nextMeeting->title,
                'start_time' => $nextMeeting->start_time->toISOString(),
            ] : null,
            'subscription' => $this->subscriptionService->getStatus($user, $institution),
        ];
    }
}
