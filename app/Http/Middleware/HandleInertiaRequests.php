<?php

namespace App\Http\Middleware;

use App\Models\Padalinys;
use App\Models\User;
use App\Actions\AuthorizeUserAndDutyByRole as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

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
        $user = $this->getLoggedInUserForInertia();

        $isSuperAdmin = false;
        $userAuthorizer = null;

        if (!is_null ($user)) {
            $isSuperAdmin = $user->hasRole(config('permission.super_admin_role_name'));
            $userAuthorizer = new Authorizer($user);
        }

        return array_merge(parent::share($request), [
            'app' => [
                'env' => fn () => config('app.env'),
                'url' => fn () => config('app.url'),
            ],
            'auth' => is_null($user) ? null : [
                'can' => fn () => [
                    'calendar' => fn () => $userAuthorizer->check('edit unit content'),
                    'content' => fn () => $userAuthorizer->check('edit unit content'),
                    'files' => fn () => $userAuthorizer->check('edit unit content'),
                    'institutions' => fn () => $userAuthorizer->check('edit institution content'),
                    'navigation' => fn () => $isSuperAdmin,
                    'saziningai' => fn () => $userAuthorizer->check('edit saziningai content'),
                    'settings' => fn () => $isSuperAdmin,
                    'users' => fn () => $userAuthorizer->check('edit unit users'),
                ],
                'user' => fn () => [
                    ...$user->toArray(), 
                    'padaliniai' => $user->padaliniai()->get(['padaliniai.id', 'padaliniai.shortname'])->unique(), 
                    'isSuperAdmin' => $isSuperAdmin,
                    'unreadNotifications' => $user->unreadNotifications()
                ],
            ],
            // is used in the admin navigation to show only the allowed pages
            'flash' => [
                'data' => fn () => $request->session()->get('data'),
                'info' => fn () => $request->session()->get('info'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'locale' => fn () => app()->getLocale(),
            'misc' => $request->session()->get('misc') ?? "",
            'padaliniai' => fn () => $this->getPadaliniaiForInertia(),
            'search' => [
                'calendar' => $request->session()->get('search_calendar') ?? [],
                'news' => $request->session()->get('search_news') ?? [],
                'pages' => $request->session()->get('search_pages') ?? [],
                'other' => $request->session()->get('search_other') ?? [],
            ],
        ]);
    }

    private function getLoggedInUserForInertia(): ?User
        {
            $user = User::withCount(['tasks' => function ($query) {
                $query->whereNull('completed_at');
            }])->with('roles', 'duties:id,name', 'duties.roles')->find(Auth::id());

            return $user;
        }

    private function getPadaliniaiForInertia(): Collection
    {
        $padaliniai = Cache::rememberForever('padaliniai-for-inertia', 
            fn () => Padalinys::where('type', '=', 'padalinys')->orderBy('shortname_vu')->get(['id', 'alias', 'shortname', 'fullname'])
        );

        return $padaliniai;
    }
}
