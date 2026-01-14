<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\TestPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Store a new push subscription for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Delete existing subscription for this endpoint to avoid duplicates
        $user->pushSubscriptions()
            ->where('endpoint', $request->endpoint)
            ->delete();

        // Create new subscription with content encoding (defaults to aesgcm if not provided)
        $user->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth'],
            $request->input('contentEncoding', 'aesgcm')
        );

        return response()->json([
            'success' => true,
            'message' => __('Push notification subscription created successfully.'),
        ]);
    }

    /**
     * Remove a push subscription for the authenticated user.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $user = Auth::user();

        $user->pushSubscriptions()
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => __('Push notification subscription removed successfully.'),
        ]);
    }

    /**
     * Send a test push notification to the authenticated user.
     */
    public function sendTest(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->pushSubscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('No push subscription found. Please enable push notifications first.'),
            ], 400);
        }

        $user->notify(new TestPushNotification);

        return response()->json([
            'success' => true,
            'message' => __('Test notification sent successfully.'),
        ]);
    }
}
