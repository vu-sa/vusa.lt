<?php

namespace App\Http\Middleware;

use App\Services\NavigationService as ServicesNavigationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GetNavigationForPublic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if method is get
        if ($request->isMethod('get')) {

            $mainNavigation = fn () => Cache::remember('mainNavigation-'.app()->getLocale(), 10, function () {
                return ServicesNavigationService::getNavigationForPublic();
            });

            Inertia::share('mainNavigation', $mainNavigation);
        }

        return $next($request);
    }
}
