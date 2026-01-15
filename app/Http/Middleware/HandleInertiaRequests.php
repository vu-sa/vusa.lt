<?php

namespace App\Http\Middleware;

use App\Enums\ModelEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ResourceServices\DutyService;
use App\Services\Typesense\TypesenseManager;
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
     * @return array<string, mixed>
     */
    public function share(Request $request)
    {
        $user = $this->getLoggedInUserForInertia();

        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

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
                    'create' => fn () => $this->getCreatePermissions($user),
                    'manageSettings' => fn () => $user->can('manage-settings'),
                    'accessAdministration' => fn () => $user->can('access-administration'),
                ],
                'user' => fn () => [
                    ...$user->toArray(),
                    'isSuperAdmin' => $isSuperAdmin,
                    'tenants' => $user->tenants()->get(['tenants.id', 'tenants.shortname', 'tenants.alias'])->unique(),
                    'unreadNotifications' => $user->unreadNotifications()->get(),
                    'tutorial_progress' => $user->tutorial_progress ?? [],
                ],
            ],
            'csrf_token' => fn () => csrf_token(),
            // 'flash' is used in the admin navigation to show only the allowed pages
            'flash' => [
                'data' => fn () => $request->session()->get('data'),
                'info' => fn () => $request->session()->get('info'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'toast_duration' => fn () => $request->session()->get('toast_duration'),
                'toast_description' => fn () => $request->session()->get('toast_description'),
            ],
            'seo' => [
                'title' => fn () => $request->session()->get('seo.title'),
            ],
            'search' => fn () => $request->session()->get('search'),
            // 'tenants' property is shared in public pages from \App\Http\Controllers\PublicController.php
            // 'tenant.banners' property is shared in public pages from \App\Http\Controllers\PublicController.php
            'tenants' => fn () => $this->getTenantsForInertia(),
            'typesenseConfig' => fn () => TypesenseManager::getFrontendConfig(),
            'pwa' => [
                'vapidPublicKey' => fn () => config('webpush.vapid.public_key'),
                'hasPushSubscription' => fn () => $user?->pushSubscriptions()->exists() ?? false,
                'subscriptionEndpoints' => fn () => $user?->pushSubscriptions()
                    ->pluck('endpoint')
                    ->toArray() ?? [],
            ],
            // Recent meetings for the NewMeetingModal (only on admin pages)
            'recentMeetings' => fn () => $this->getRecentMeetingsForInertia($request, $user),
        ]);
    }

    private function getLoggedInUserForInertia(): ?User
    {
        $user = User::query()
            ->withCount(['tasks' => function ($query) {
                $query->whereNull('completed_at');
            }])
            ->with('roles', 'current_duties:id,name,institution_id', 'current_duties.roles', 'current_duties.institution:id,name')
            ->find(Auth::id());

        return $user;
    }

    /**
     * @return Collection<int, Tenant>
     */
    private function getTenantsForInertia(): Collection
    {
        // TODO: maybe should return all tenants, even pagrindinis
        $tenants = Cache::rememberForever('all-tenants-for-inertia',
            fn () => Tenant::orderBy('shortname_vu')->get(['id', 'alias', 'shortname', 'fullname', 'type', 'primary_institution_id'])
        );

        $tenants->load('primary_institution:id,short_name,image_url');

        return $tenants;
    }

    /**
     * @return array<string, bool>
     */
    private function getIndexPermissions(User $user): array
    {
        return Cache::remember('index-permissions-'.$user->id, 1800, function () use ($user) {
            $labels = ModelEnum::toLabels();

            // remove where value is reservationResource
            // TODO: maybe needs better solution
            unset($labels[array_search('reservationResource', $labels)]);
            // TODO: file is also a special case, since it isn't linked to models, but has permissions
            unset($labels[array_search('file', $labels)]);

            return collect($labels)
                ->mapWithKeys(function ($model) use ($user) {
                    return [$model => $user->can('viewAny', ['App\\Models\\'.ucfirst($model)])];
                })->toArray();
        });
    }

    /**
     * @return array<string, bool>
     */
    private function getCreatePermissions(User $user): array
    {
        return Cache::remember('create-permissions-'.$user->id, 1800, function () use ($user) {
            $labels = ModelEnum::toLabels();

            // remove where value is reservationResource
            // TODO: maybe needs better solution
            unset($labels[array_search('reservationResource', $labels)]);
            unset($labels[array_search('file', $labels)]);

            return collect($labels)
                ->mapWithKeys(function ($model) use ($user) {
                    return [$model => $user->can('create', ['App\\Models\\'.ucfirst($model)])];
                })->toArray();
        });
    }

    /**
     * Get recent meetings for the NewMeetingModal shared data.
     * Only loaded on admin pages (path starts with 'mano').
     */
    private function getRecentMeetingsForInertia(Request $request, ?User $user): ?array
    {
        // Only load for admin pages
        if (! str_starts_with($request->path(), 'mano') || ! $user) {
            return null;
        }

        // Get user's institutions with meetings
        $userInstitutions = DutyService::getUserInstitutionsForDashboard();

        if ($userInstitutions->isEmpty()) {
            return [];
        }

        $sixMonthsAgo = now()->subMonths(6);

        return $userInstitutions
            ->flatMap(function ($institution) use ($sixMonthsAgo) {
                return $institution->meetings
                    ?->filter(fn ($meeting) => $meeting->start_time >= $sixMonthsAgo)
                    ->map(fn ($meeting) => [
                        'id' => (string) $meeting->id,
                        'title' => $meeting->title,
                        'start_time' => $meeting->start_time?->toISOString(),
                        'institution_id' => (string) $institution->id,
                        'institution_name' => $institution->name ?? 'Unknown',
                        'agenda_items' => $meeting->agendaItems->map(fn ($item) => ['title' => $item->title])->toArray(),
                    ]) ?? collect();
            })
            ->sortByDesc('start_time')
            ->unique('id')
            ->take(10)
            ->values()
            ->toArray();
    }
}
