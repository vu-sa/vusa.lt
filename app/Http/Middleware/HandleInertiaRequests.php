<?php

namespace App\Http\Middleware;

use App\Models\EventType;
use App\Models\Institution;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use App\Services\DeviceMetricService;
use App\Services\Permissions\PermissionMapBuilder;
use App\Services\Typesense\TypesenseManager;
use App\Settings\SiteSettings;
use App\Support\AuthorityCacheExpiry;
use App\Support\MorphMap;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    public const TENANTS_CACHE_KEY = 'all-tenants-for-inertia';

    public const EVENT_TYPES_CACHE_KEY = 'all-event-types-for-inertia';

    public const TAGS_CACHE_KEY = 'all-tags-for-inertia';

    public const INSTITUTION_TYPES_CACHE_KEY = 'all-institution-types-for-inertia';

    /**
     * Cached forever; each owning model forgets its key on write.
     */
    public const SHARED_CACHE_KEYS = [
        self::TENANTS_CACHE_KEY,
        self::EVENT_TYPES_CACHE_KEY,
        self::TAGS_CACHE_KEY,
        self::INSTITUTION_TYPES_CACHE_KEY,
    ];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    #[\Override]
    protected $rootView = 'app';

    #[\Override]
    public function handle(Request $request, Closure $next)
    {
        $ssrEligible = config('inertia.ssr.enabled') && $request->user() === null
            && $request->routeIs(...config('inertia.ssr.routes'));

        Inertia::disableSsr(! $ssrEligible);

        // Only full page loads are server-rendered; XHR visits already have the client's @routes.
        if ($ssrEligible && ! $request->header('X-Inertia')) {
            Inertia::share('ziggy', fn () => [
                ...(new Ziggy)->filter(collect(Route::getRoutes())->filter(
                    fn ($route) => ! str_starts_with($route->uri(), 'api/')
                        && (! str_starts_with($route->uri(), 'mano') || $route->getName() === 'login')
                )->map->getName()->filter()->all())->toArray(),
                'location' => $request->url(),
            ]);
        }

        $this->recordPwaLaunchIfDetected($request);

        return parent::handle($request, $next);
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
     * @return string|null
     */
    #[\Override]
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
    #[\Override]
    public function share(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        // Admin-only data stays off public pages, where no component reads it.
        $onAdmin = $request->is('mano', 'mano/*');

        $pushEndpoints = null;
        $getPushEndpoints = function () use ($user, &$pushEndpoints): array {
            return $pushEndpoints ??= $user?->pushSubscriptions()->pluck('endpoint')->all() ?? [];
        };

        return array_merge(parent::share($request), [
            'app' => [
                'env' => fn () => config('app.env'),
                'locale' => fn () => app()->getLocale(),
                'path' => $request->path(...),
                'url' => fn () => config('app.url'),
            ],
            // Organisation-level facts (contact addresses, social profiles, registry details)
            // so the footer, error pages and navigation buttons stop hardcoding their own
            // copies. See config/vusa.php.
            'organization' => fn () => [
                'contacts' => config('vusa.contacts'),
                'social' => config('vusa.social'),
                'legal' => config('vusa.legal'),
                // Resolved server-side so the cookie banner links to the right language
                // record without knowing anything about permalinks. Null when unconfigured.
                'privacyPageUrl' => SiteSettings::cachedPrivacyPageUrl(app()->getLocale()),
            ],
            'auth' => is_null($user) ? null : [
                'can' => fn () => [
                    'index' => fn () => $onAdmin ? $this->getIndexPermissions($user) : [],
                    'create' => fn () => $onAdmin ? $this->getCreatePermissions($user) : [],
                    'forceDelete' => fn () => $onAdmin ? $this->getForceDeletePermissions($user) : [],
                ],
                'user' => fn () => [
                    // Relations loaded on the request user by policies or controllers are not shared.
                    ...$user->withoutRelations()->toArray(),
                    ...($onAdmin ? $this->getOpenTaskCounts($user) : []),
                    'isSuperAdmin' => $user->isSuperAdmin(),
                    'tenants' => $onAdmin
                        ? $user->tenants()->distinct()->get(['tenants.id', 'tenants.shortname', 'tenants.alias'])
                        : [],
                    'unreadNotifications' => $onAdmin ? $user->unreadNotifications()->get() : [],
                    'tutorial_progress' => $user->tutorial_progress ?? [],
                    'ui_preferences' => $user->ui_preferences ?? [],
                ],
                'impersonating' => fn () => $this->getImpersonationState($request),
            ],
            'csrf_token' => csrf_token(...),
            // 'flash' is used in the admin navigation to show only the allowed pages
            'flash' => [
                'data' => fn () => $request->session()->get('data'),
                'info' => fn () => $request->session()->get('info'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'toast_duration' => fn () => $request->session()->get('toast_duration'),
                'toast_description' => fn () => $request->session()->get('toast_description'),
                'access_change_warning' => fn () => $request->session()->get('access_change_warning'),
            ],
            'seo' => [
                'title' => fn () => $request->session()->get('seo.title'),
            ],
            'search' => fn () => $request->session()->get('search'),
            // 'tenants' property is shared in public pages from \App\Http\Controllers\PublicController.php
            // 'tenant.banners' property is shared in public pages from \App\Http\Controllers\PublicController.php
            'tenants' => $this->getTenantsForInertia(...),
            // Global, not tenant-scoped, a handful of rows repo-wide — cheap enough to
            // always share rather than thread an `eventTypes` prop through every
            // controller/form that needs an event-type picker (Calendar admin form,
            // RichContent's event-list/calendar block editors). See QuickLinkController's
            // identical "not worth a search endpoint" rationale for topics.
            'eventTypes' => fn () => $onAdmin ? $this->getEventTypesForInertia() : [],
            'tags' => fn () => $onAdmin ? $this->getTagsForInertia() : [],
            'institutionTypes' => $this->getInstitutionTypesForInertia(...),
            'typesenseConfig' => TypesenseManager::getFrontendConfig(...),
            // CARTO now requires an API key on basemap tile requests (PadalinysMap, EventLocationMap).
            'map' => [
                'cartoApiKey' => fn () => config('services.carto.api_key'),
            ],
            'pwa' => [
                'vapidPublicKey' => fn () => config('webpush.vapid.public_key'),
                'hasPushSubscription' => fn () => $onAdmin && $getPushEndpoints() !== [],
                'subscriptionEndpoints' => fn () => $onAdmin ? $getPushEndpoints() : [],
            ],
            // The navigation catalog (O19): one server-side definition of every admin
            // destination, gated and cached per user. Null outside `/mano` so public pages pay
            // one string comparison instead of resolving permissions nobody asked for.
            'adminNavigation' => fn () => $user && $onAdmin
                ? app(AdminNavigationCatalog::class)->for($user)
                : null,
        ]);
    }

    /**
     * Open and overdue task counts for the admin shell badges, in one query.
     *
     * @return array{tasks_count: int, overdue_tasks_count: int}
     */
    private function getOpenTaskCounts(User $user): array
    {
        $counts = $user->tasks()
            ->whereNull('completed_at')
            ->toBase()
            ->selectRaw('count(*) as tasks_count')
            ->selectRaw('sum(case when due_date < ? then 1 else 0 end) as overdue_tasks_count', [now()])
            ->first();

        return [
            'tasks_count' => (int) ($counts->tasks_count ?? 0),
            'overdue_tasks_count' => (int) ($counts->overdue_tasks_count ?? 0),
        ];
    }

    /**
     * @return Collection<int, Tenant>
     */
    private function getTenantsForInertia(): Collection
    {
        // Institution saves forget this key too, since the primary institution is cached with it.
        return Cache::rememberForever(self::TENANTS_CACHE_KEY,
            fn () => Tenant::orderBy('shortname_vu')
                ->with('primary_institution:id,short_name,image_url,image_focal_point')
                ->get(['id', 'alias', 'shortname', 'fullname', 'type', 'primary_institution_id'])
        );
    }

    /**
     * @return Collection<int, EventType>
     */
    private function getEventTypesForInertia(): Collection
    {
        return Cache::rememberForever(self::EVENT_TYPES_CACHE_KEY,
            fn () => EventType::orderBy('sort_order')->get(['id', 'name', 'slug'])
        );
    }

    /**
     * @return Collection<int, Tag>
     */
    private function getTagsForInertia(): Collection
    {
        return Cache::rememberForever(self::TAGS_CACHE_KEY,
            fn () => Tag::orderBy('alias')->get(['id', 'name', 'alias', 'is_topic'])
        );
    }

    /**
     * @return Collection<int, Type>
     */
    private function getInstitutionTypesForInertia(): Collection
    {
        return Cache::rememberForever(self::INSTITUTION_TYPES_CACHE_KEY,
            fn () => Type::where('model_type', MorphMap::alias(Institution::class))->get(['id', 'title', 'slug'])
        );
    }

    /**
     * @return array{impersonator_name: string}|null
     */
    private function getImpersonationState(Request $request): ?array
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return null;
        }

        $impersonator = User::select('name')->find($impersonatorId);

        return $impersonator ? ['impersonator_name' => $impersonator->name] : null;
    }

    /**
     * @return array<string, bool>
     */
    private function getIndexPermissions(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::INDEX_CACHE_PREFIX.$user->id, fn () => AuthorityCacheExpiry::for($user, 1800),
            fn () => app(PermissionMapBuilder::class)->indexMap($user)
        );
    }

    /**
     * @return array<string, bool>
     */
    private function getCreatePermissions(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::CREATE_CACHE_PREFIX.$user->id, fn () => AuthorityCacheExpiry::for($user, 1800),
            fn () => app(PermissionMapBuilder::class)->createMap($user)
        );
    }

    /**
     * @return array<string, bool>
     */
    private function getForceDeletePermissions(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::FORCE_DELETE_CACHE_PREFIX.$user->id, fn () => AuthorityCacheExpiry::for($user, 1800),
            fn () => app(PermissionMapBuilder::class)->forceDeleteMap($user)
        );
    }

    public static function adminNavigationCacheKey(string $userId): string
    {
        return AdminNavigationCatalog::CACHE_PREFIX.$userId;
    }

    private function recordPwaLaunchIfDetected(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $isPwa = $request->query('source') === 'pwa'
            || ($request->cookie('pwa_mode') === '1' && $request->is('mano*'));

        if ($isPwa && ! $request->session()->has('pwa_launch_recorded')) {
            $request->session()->put('pwa_launch_recorded', true);
            app(DeviceMetricService::class)->recordPwaLaunch();
        }
    }
}
