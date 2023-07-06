<?php

namespace App\Http\Middleware;

use App\Enums\ModelEnum;
use App\Models\ChangelogItem;
use App\Models\Padalinys;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
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
     *
     * @return array
     */
    public function share(Request $request)
    {
        $user = $this->getLoggedInUserForInertia();

        $isSuperAdmin = false;

        if (! is_null($user)) {
            $isSuperAdmin = $user->hasRole(config('permission.super_admin_role_name'));
        }

        return array_merge(parent::share($request), [
            'app' => [
                'env' => fn () => config('app.env'),
                'locale' => fn () => app()->getLocale(),
                'path' => fn () => $request->path(),
                'url' => fn () => config('app.url'),
            ],
            'auth' => is_null($user) ? null : [
                'can' => fn () => [
                    'index' => fn () => $this->getIndexPermissions($user),
                ],
                'changes' => fn () => $this->getChangesForUser($user),
                'user' => fn () => [
                    ...$user->toArray(),
                    'isSuperAdmin' => $isSuperAdmin,
                    'padaliniai' => $user->padaliniai()->get(['padaliniai.id', 'padaliniai.shortname', 'padaliniai.alias'])->unique(),
                    'unreadNotifications' => $user->unreadNotifications,
                ],
            ],
            // is used in the admin navigation to show only the allowed pages
            'flash' => [
                'data' => fn () => $request->session()->get('data'),
                'info' => fn () => $request->session()->get('info'),
                'success' => fn () => $request->session()->get('success'),
            ],
            // 'layout' => is_null($user) ? null : [
            //     'navBackground' => null
            // ],
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
        }])->with('roles', 'duties:id,name,institution_id', 'duties.roles', 'duties.institution:id,name')->find(Auth::id());

        return $user;
    }

    private function getPadaliniaiForInertia(): Collection
    {
        // TODO: maybe should return all 'padaliniai', even pagrindinis
        $padaliniai = Cache::rememberForever('padaliniai-for-inertia',
            fn () => Padalinys::where('type', '=', 'padalinys')->orderBy('shortname_vu')->get(['id', 'alias', 'shortname', 'fullname'])
        );

        return $padaliniai;
    }

    private function getIndexPermissions(User $user)
    {
        return Cache::remember('index-permissions-'.$user->id, 3600, function () use ($user) {
            $authorizer = new Authorizer();

            $labels = ModelEnum::toLabels();

            // remove where value is reservationResource
            // TODO: maybe needs better solution
            unset($labels[array_search('reservationResource', $labels)]);

            return collect($labels)
                ->mapWithKeys(function ($model) use ($user, $authorizer) {
                    return [$model => $user->can('viewAny', ['App\\Models\\'.ucfirst($model), $authorizer])];
                })->toArray();
        });
    }

    private function getChangesForUser(User $user)
    {
        $user->makeVisible('last_changelog_check');

        if (is_null($user->last_changelog_check)) {
            return ChangelogItem::query()->orderBy('date', 'desc')->get();
        }

        $changes = ChangelogItem::query()->whereDate('date', '>', $user->last_changelog_check)->get();

        return $changes;
    }
}
