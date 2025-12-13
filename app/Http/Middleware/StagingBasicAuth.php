<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to protect staging environment with HTTP Basic Auth.
 *
 * This middleware is only active when APP_ENV=staging and provides
 * a simple username/password gate using STAGING_USER and STAGING_PASSWORD
 * environment variables.
 *
 * Advantages over `php artisan down --secret`:
 * - Works with all routes including API endpoints
 * - Doesn't require cookie support (works with curl, Postman, etc.)
 * - Credentials can be easily shared with testers
 * - Session persists across browser restarts
 */
class StagingBasicAuth
{
    /**
     * Routes that should bypass authentication (e.g., health checks).
     */
    protected array $except = [
        'up',
        'health',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply on staging environment
        if (config('app.env') !== 'staging') {
            return $next($request);
        }

        // Skip for excepted routes
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // Check if credentials are configured
        $username = config('app.staging_user');
        $password = config('app.staging_password');

        if (empty($username) || empty($password)) {
            // If not configured, allow access (fail open for flexibility)
            return $next($request);
        }

        // Validate Basic Auth credentials
        $providedUser = $request->getUser();
        $providedPassword = $request->getPassword();

        if ($providedUser === $username && $providedPassword === $password) {
            return $next($request);
        }

        // Return 401 with Basic Auth challenge
        return response('Staging environment - authentication required.', 401)
            ->header('WWW-Authenticate', 'Basic realm="VU SA Staging"');
    }

    /**
     * Determine if the request should pass through without authentication.
     */
    protected function shouldPassThrough(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
