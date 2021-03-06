<?php

namespace App\Http\Middleware;

use App\Models\Calendar;
use App\Models\Navigation;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Padalinys;
use Illuminate\Support\Facades\Auth;
use App\Models\Page;
use App\Models\SaziningaiExam;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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
        $user = User::find(Auth::id());
        if ($user) {
            $user->padalinys = User::find(Auth::id())?->padalinys()?->shortname;
        }

        return array_merge(parent::share($request), [
            'app' => [
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
            // is used in the admin navigation to show only the allowed pages
            'can' => is_null($request->user()) ? false : [
                'content' => $request->user()->can('viewAny', Page::class),
                'users' => $request->user()->can('viewAny', User::class),
                'navigation' => $request->user()->can('viewAny', Navigation::class),
                'calendar' => $request->user()->can('viewAny', Calendar::class),
                'files' => true,
                'saziningai' => $request->user()->can('viewAny', SaziningaiExam::class),
            ],
            'locale' => function () {
                return app()->getLocale();
            },
            'misc' => $request->session()->get('misc') ?? "",
            'padaliniai' => Padalinys::where('type', '=', 'padalinys')->orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'alias' => $padalinys->alias,
                    'shortname' => $padalinys->shortname,
                    'fullname' => $padalinys->fullname,
                ];
            }),
            'search' => [
                'calendar' => $request->session()->get('search_calendar') ?? [],
                'news' => $request->session()->get('search_news') ?? [],
                'pages' => $request->session()->get('search_pages') ?? [],
                'other' => $request->session()->get('search_other') ?? [],
            ],
            // current user
            'user' => $user,
        ]);
    }
}
