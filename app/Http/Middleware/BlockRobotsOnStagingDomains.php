<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Block indexing of staging pages and preview domains. */
class BlockRobotsOnStagingDomains
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('robots.txt')) {
            $host = $request->getHost();

            if (str_contains($host, 'naujas.vusa.lt')) {
                return response("User-agent: *\nDisallow: /", 200)
                    ->header('Content-Type', 'text/plain');
            }
        }

        $response = $next($request);

        if (config('app.env') === 'staging') {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
