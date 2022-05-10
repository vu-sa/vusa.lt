<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Padalinys;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'app' => [
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
            'padaliniai' => Padalinys::where('type', '=', 'padalinys')->orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'alias' => $padalinys->alias,
                    'shortname' => $padalinys->shortname,
                    'fullname' => $padalinys->fullname,
                ];
            }),

            'locale' => function () {
                return app()->getLocale();
            },
            'language' => function () {
                if(!file_exists(resource_path('lang/'. app()->getLocale() .'.json'))) {
                    return [];
                }
                return json_decode(file_get_contents(resource_path('lang/'. app()->getLocale() .'.json')), true);   
            },
            'search' => fn () => [
                'calendar' => $request->session()->get('search_calendar') ?? [],
                'news' => $request->session()->get('search_news') ?? [],
                'pages' => $request->session()->get('search_pages') ?? [],
                'other' => $request->session()->get('search_other') ?? [],
            ]


            // 'flash' => fn () => [
            //     'success' => $request->session()->get('success'),
            //     'info' => $request->session()->get('info'),
            //     'warning' => $request->session()->get('warning'),
            //     'error' => $request->session()->get('error'),
            // ]
        ]);
    }
}
