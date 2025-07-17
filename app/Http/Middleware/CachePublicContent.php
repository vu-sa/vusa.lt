<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicContent
{
    /**
     * Handle an incoming request for public content caching.
     */
    public function handle(Request $request, Closure $next, int $ttl = 3600): Response
    {
        // Only cache GET requests for guests
        if ($request->method() !== 'GET' || auth()->check()) {
            return $next($request);
        }

        // Don't cache requests with query parameters (search, pagination, etc.)
        if ($request->query()) {
            return $next($request);
        }

        // Create a unique cache key based on URL, locale, and tenant
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $ttl, function () use ($request, $next) {
            return $next($request);
        });
    }

    /**
     * Generate a unique cache key for the request.
     */
    private function getCacheKey(Request $request): string
    {
        $parts = [
            'public_page',
            $request->getHost(),
            $request->getPathInfo(),
            app()->getLocale(),
        ];

        return implode('_', array_map('md5', $parts));
    }
}
