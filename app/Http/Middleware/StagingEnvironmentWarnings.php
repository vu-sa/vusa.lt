<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to share staging environment information with the frontend.
 *
 * This allows Vue components to display warnings about shared resources
 * and the staging environment status.
 */
class StagingEnvironmentWarnings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'staging') {
            Inertia::share('staging', [
                'isStaging' => true,
                'filesReadOnly' => config('app.files_read_only', false),
                'sharepointReadOnly' => config('app.sharepoint_read_only', false),
            ]);
        }

        return $next($request);
    }
}
