<?php

namespace App\Http\Middleware;

use App\Services\NavigationService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class GetNavigationForPublic
{
    /**
     * Handle an incoming request.
     *
     * `NavigationService::getNavigationForPublic()` already caches per locale — this
     * used to wrap it in a second `mainNavigation-{locale}` cache layer with its own
     * invalidation, which just meant two keys could disagree after an edit.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('get')) {
            Inertia::share('mainNavigation', fn () => NavigationService::getNavigationForPublic());
            Inertia::share('footerNavigation', fn () => NavigationService::getFooterNavigationForPublic());
        }

        return $next($request);
    }
}
