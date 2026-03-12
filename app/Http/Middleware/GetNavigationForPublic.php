<?php

namespace App\Http\Middleware;

use App\Services\NavigationService as ServicesNavigationService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GetNavigationForPublic
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if method is get
        if ($request->isMethod('get')) {

            $mainNavigation = fn () => Cache::remember('mainNavigation-'.app()->getLocale(), 3600, function () {
                return ServicesNavigationService::getNavigationForPublic();
            });

            Inertia::share('mainNavigation', $mainNavigation);
        }

        return $next($request);
    }
}
