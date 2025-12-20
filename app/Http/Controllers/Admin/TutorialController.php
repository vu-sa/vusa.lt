<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorialController extends Controller
{
    /**
     * Mark a tutorial/tour as completed for the current user.
     *
     * Stores the tour ID with completion timestamp in the user's tutorial_progress JSON column.
     * Tour IDs should follow the format: {page}-{section}-v{version} (e.g., "atstovavimas-overview-v1")
     */
    public function markCompleted(Request $request): RedirectResponse
    {
        $request->validate([
            'tour_id' => 'required|string|max:100',
        ]);

        $user = User::find(Auth::id());

        if (! $user) {
            return back();
        }

        $tourId = $request->input('tour_id');
        $progress = $user->tutorial_progress ?? [];

        // Only mark as completed if not already completed
        if (! isset($progress[$tourId])) {
            $progress[$tourId] = now()->toIso8601String();
            $user->tutorial_progress = $progress;
            $user->save();
        }

        return back();
    }

    /**
     * Get the tutorial progress for the current user.
     *
     * Returns the user's tutorial_progress JSON object with all completed tour IDs.
     * This endpoint returns JSON as it's used for AJAX calls, not page navigation.
     */
    public function getProgress(): \Illuminate\Http\JsonResponse
    {
        $user = User::find(Auth::id());

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'progress' => $user->tutorial_progress ?? [],
        ]);
    }

    /**
     * Reset a specific tutorial for the current user (allows replay).
     */
    public function resetTour(Request $request): RedirectResponse
    {
        $request->validate([
            'tour_id' => 'required|string|max:100',
        ]);

        $user = User::find(Auth::id());

        if (! $user) {
            return back();
        }

        $tourId = $request->input('tour_id');
        $progress = $user->tutorial_progress ?? [];

        if (isset($progress[$tourId])) {
            unset($progress[$tourId]);
            $user->tutorial_progress = $progress;
            $user->save();
        }

        return back();
    }

    /**
     * Reset all tutorials for the current user.
     *
     * Clears all tutorial progress, allowing all tours to be shown again.
     */
    public function resetAll(): RedirectResponse
    {
        $user = User::find(Auth::id());

        if (! $user) {
            return back();
        }

        $user->tutorial_progress = [];
        $user->save();

        return back();
    }
}
