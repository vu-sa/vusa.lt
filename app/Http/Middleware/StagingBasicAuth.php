<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protect staging with HTTP Basic Auth when its gate is enabled. */
class StagingBasicAuth
{
    protected array $except = [
        'up',
        'health',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'staging' || ! config('app.staging_basic_auth_enabled', true)) {
            return $next($request);
        }

        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        $username = config('app.staging_user');
        $password = config('app.staging_password');

        if (empty($username) || empty($password)) {
            return response('Staging environment is unavailable because HTTP authentication is not configured.', 503);
        }

        $providedUser = $request->getUser();
        $providedPassword = $request->getPassword();

        if ($providedUser === $username && $providedPassword === $password) {
            return $next($request);
        }

        return response('Staging environment - authentication required.', 401)
            ->header('WWW-Authenticate', 'Basic realm="VU SA Staging"');
    }

    protected function shouldPassThrough(Request $request): bool
    {
        return array_any($this->except, fn ($except) => $request->is($except));
    }
}
