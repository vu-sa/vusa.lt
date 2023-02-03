<?php

namespace App\Http\Middleware;

use App\Models\Navigation;
use App\Models\Padalinys;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GetNavigationForPublic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if method is get
        if ($request->isMethod('get'))
        {
            $mainNavigation = fn () => Cache::remember('mainNavigation-' . app()->getLocale(), 3600, function () {
                
                $vusa = Padalinys::where('shortname', 'VU SA')->first();
                return Navigation::where([['padalinys_id', $vusa->id], ['lang', app()->getLocale()]])->orderBy('order')->get();
            
            });

            Inertia::share('mainNavigation', $mainNavigation);
        }

        return $next($request);
    }
}
