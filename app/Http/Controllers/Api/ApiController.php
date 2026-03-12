<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Base controller for API endpoints.
 *
 * This controller provides:
 * - Consistent JSON response helpers via ApiResponses trait
 * - Standard authorization handling
 * - Common patterns for API controllers
 *
 * Use this for:
 * - Public API routes (/api/v1/*)
 * - Admin API routes (/api/v1/admin/*)
 */
abstract class ApiController extends Controller
{
    use ApiResponses;

    /**
     * Handle authorization with JSON error responses.
     *
     * Unlike Inertia controllers that redirect on forbidden,
     * API controllers return 403 JSON responses.
     *
     * @param  string  $ability  The ability to check
     * @param  mixed  $arguments  Model or class to authorize against
     */
    protected function authorizeApi(string $ability, mixed $arguments): void
    {
        $this->authorize($ability, $arguments);
    }

    /**
     * Safely get the authenticated user or return 401.
     *
     * @throws UnauthorizedHttpException
     */
    protected function requireAuth(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthorized');
        }

        return $user;
    }
}
