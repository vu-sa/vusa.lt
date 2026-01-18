<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Typesense\TypesenseManager;
use App\Services\Typesense\TypesenseScopedKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for admin search configuration and scoped API key management.
 *
 * Provides endpoints for:
 * - Getting Typesense configuration with scoped API keys
 * - Refreshing expired scoped keys
 */
class SearchApiController extends Controller
{
    /**
     * Get Typesense configuration with scoped API key for the authenticated user.
     *
     * Returns a user-specific scoped API key that embeds tenant filtering,
     * making it impossible to bypass authorization on the client side.
     */
    public function config(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! TypesenseScopedKeyService::isConfigured()) {
            return response()->json([
                'error' => 'Typesense is not configured for scoped keys',
                'fallback' => true,
            ], 503);
        }

        $config = TypesenseManager::getAdminFrontendConfig($user);

        return response()->json($config);
    }

    /**
     * Refresh the scoped API key for the authenticated user.
     *
     * Call this endpoint when the frontend detects a 401 error from Typesense,
     * indicating the scoped key has expired.
     */
    public function refreshKey(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! TypesenseScopedKeyService::isConfigured()) {
            return response()->json([
                'error' => 'Typesense is not configured for scoped keys',
                'fallback' => true,
            ], 503);
        }

        // Clear the cached key to force regeneration
        TypesenseScopedKeyService::invalidateForUser($user->id);

        // Get fresh configuration with new scoped key
        $config = TypesenseManager::getAdminFrontendConfig($user);

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }
}
