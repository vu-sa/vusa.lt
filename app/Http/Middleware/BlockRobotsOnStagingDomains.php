<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to block search engine indexing on staging/preview domains.
 *
 * For domains containing 'naujas.vusa.lt', this middleware intercepts
 * robots.txt requests and returns a disallow-all directive.
 * All other requests pass through normally.
 */
class BlockRobotsOnStagingDomains
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only intercept robots.txt requests
        if ($request->is('robots.txt')) {
            $host = $request->getHost();

            // Block all robots on naujas.vusa.lt and its subdomains
            if (str_contains($host, 'naujas.vusa.lt')) {
                return response("User-agent: *\nDisallow: /", 200)
                    ->header('Content-Type', 'text/plain');
            }
        }

        // Pass through to static file or other routes
        return $next($request);
    }
}
