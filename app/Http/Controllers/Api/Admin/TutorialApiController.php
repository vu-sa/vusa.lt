<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorialApiController extends ApiController
{
    /**
     * Get tutorial progress for the current user.
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        return $this->jsonSuccess([
            'progress' => $user->tutorial_progress ?? [],
        ]);
    }

    /**
     * Mark a tutorial/tour as completed for the current user.
     *
     * Stores the tour ID with completion timestamp in the user's tutorial_progress JSON column.
     * Tour IDs should follow the format: {page}-{section}-v{version} (e.g., "atstovavimas-overview-v1")
     * or spotlight-{feature}-v{version} for spotlights (e.g., "spotlight-tenant-tab-v1")
     */
    public function markComplete(Request $request): JsonResponse
    {
        $request->validate([
            'tour_id' => 'required|string|max:100',
        ]);

        $user = $this->requireAuth($request);

        $tourId = $request->input('tour_id');
        $progress = $user->tutorial_progress ?? [];

        // Only mark as completed if not already completed
        if (! isset($progress[$tourId])) {
            // Check if this is the user's first tutorial completion
            $isFirstTutorial = empty($progress);

            $progress[$tourId] = now()->toIso8601String();
            $user->tutorial_progress = $progress;
            $user->save();

            // Send welcome notification on first tutorial completion
            if ($isFirstTutorial) {
                $user->notify(new WelcomeNotification);
            }
        }

        return $this->jsonSuccess([
            'progress' => $progress,
        ]);
    }

    /**
     * Reset a specific tutorial for the current user (allows replay).
     */
    public function resetTour(Request $request): JsonResponse
    {
        $request->validate([
            'tour_id' => 'required|string|max:100',
        ]);

        $user = $this->requireAuth($request);

        $tourId = $request->input('tour_id');
        $progress = $user->tutorial_progress ?? [];

        if (isset($progress[$tourId])) {
            unset($progress[$tourId]);
            $user->tutorial_progress = $progress;
            $user->save();
        }

        return $this->jsonSuccess([
            'progress' => $progress,
        ]);
    }

    /**
     * Reset all tutorials for the current user.
     */
    public function resetAll(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $user->tutorial_progress = [];
        $user->save();

        return $this->jsonSuccess([
            'progress' => [],
        ]);
    }
}
