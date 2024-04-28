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
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if method is get
        if ($request->isMethod('get')) {
            $mainNavigation = fn () => Cache::remember('mainNavigation-'.app()->getLocale(), 10, function () {
                $vusa = Padalinys::where('shortname', 'VU SA')->first();

                $navigation = Navigation::where([['padalinys_id', $vusa->id], ['lang', app()->getLocale()]])->orderBy('order')->get();

                $rootNavigation = $navigation->where('parent_id', 0)->values()->toArray();

                for ($i = 0; $i < count($rootNavigation); $i++) {
                    // Make array of arrays of links, by columns in extra_attributes
                    $rootNavigation[$i]['links'] = [];

                    $children = $navigation->where('parent_id', $rootNavigation[$i]['id'])->values()->toArray();

                    ## Expand extra_attributes to own keys
                    foreach ($children as $key => $child) {
                        $extraAttributes = $child['extra_attributes'];
                        unset($child['extra_attributes']);

                        foreach ($extraAttributes as $extraKey => $extraValue) {
                            $child[$extraKey] = $extraValue;
                        }

                        $children[$key] = $child;
                    }

                    for ($j = 1; $j <= 3; $j++) {
                        // Push array to root links, where extra_attributes['column'] == $j
                        $rootNavigation[$i]['links'][] = array_filter($children, fn ($child) => $child['column'] == $j);
                    }

                    // Remove empty arrays
                    $rootNavigation[$i]['links'] = array_filter($rootNavigation[$i]['links']);

                    // Add column count
                    $rootNavigation[$i]['cols'] = count($rootNavigation[$i]['links']);
                }

                return $rootNavigation;
            });

            Inertia::share('mainNavigation', $mainNavigation);
        }

        return $next($request);
    }
}
