<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\TestPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Get all push subscriptions for the authenticated user.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \Illuminate\Database\Eloquent\Collection<int, PushSubscription> $subscriptions */
        $subscriptions = $user->pushSubscriptions()
            ->select(['id', 'endpoint', 'device_name', 'created_at', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        $subscriptions = $subscriptions->map(function ($subscription) {
            $createdAt = $subscription->getAttribute('created_at');
            $updatedAt = $subscription->getAttribute('updated_at');

            return [
                'id' => $subscription->getKey(),
                'endpoint' => $subscription->getAttribute('endpoint'),
                'device_name' => $subscription->getAttribute('device_name'),
                'created_at' => $createdAt?->toIso8601String(),
                'updated_at' => $updatedAt?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
        ]);
    }

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
            'deviceName' => 'nullable|string|max:255',
        ]);

        /** @var \App\Models\User $user */
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

        // Update device name if provided
        if ($request->filled('deviceName')) {
            $user->pushSubscriptions()
                ->where('endpoint', $request->endpoint)
                ->update(['device_name' => $request->input('deviceName')]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Push notification subscription created successfully.'),
        ]);
    }

    /**
     * Remove a push subscription for the authenticated user by endpoint.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $deleted = $user->pushSubscriptions()
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? __('Push notification subscription removed successfully.')
                : __('Subscription not found.'),
        ]);
    }

    /**
     * Remove a push subscription for the authenticated user by ID.
     */
    public function destroyById(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $deleted = $user->pushSubscriptions()
            ->where('id', $id)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? __('Push notification subscription removed successfully.')
                : __('Subscription not found.'),
        ]);
    }

    /**
     * Send a test push notification to the authenticated user.
     */
    public function sendTest(): JsonResponse
    {
        /** @var \App\Models\User $user */
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
