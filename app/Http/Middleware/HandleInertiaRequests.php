<?php

namespace App\Http\Middleware;

use App\Models\EventType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use App\Services\DeviceMetricService;
use App\Services\Permissions\PermissionMapBuilder;
use App\Services\SystemDutyTypes;
use App\Services\Typesense\TypesenseManager;
use App\Settings\SiteSettings;
use App\Support\AuthorityCacheExpiry;
use App\Support\ContentKeyedOnceProp;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    public const TENANTS_CACHE_KEY = 'all-tenants-for-inertia';

    public const EVENT_TYPES_CACHE_KEY = 'all-event-types-for-inertia';

    public const TAGS_CACHE_KEY = 'all-tags-for-inertia';

    public const INSTITUTION_TYPES_CACHE_KEY = 'all-institution-types-for-inertia:v2';

    public const NOTIFICATION_PREVIEW_LIMIT = 20;

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

        // The renderer receives its public route list; browser visits use the built route artifact.
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

    #[\Override]
    public function shareOnce(Request $request): array
    {
        $onAdmin = $request->is('mano', 'mano/*');
        $catalogs = [
            'tenants' => fn () => $this->getTenantsForInertia()->toArray(),
            'tags' => fn () => $onAdmin ? $this->getTagsForInertia()->toArray() : [],
            'eventTypes' => fn () => $onAdmin ? $this->getEventTypesForInertia()->toArray() : [],
            'institutionTypes' => fn () => $this->getInstitutionTypesForInertia()->toArray(),
            'curatorDutyTypeIds' => fn () => $onAdmin ? app(SystemDutyTypes::class)->curatorIds() : [],
            'organization' => fn () => [
                'contacts' => config('vusa.contacts'),
                'social' => config('vusa.social'),
                'legal' => config('vusa.legal'),
                'privacyPageUrl' => SiteSettings::cachedPrivacyPageUrl(app()->getLocale()),
            ],
        ];
        $props = ['map' => fn () => ['cartoApiKey' => config('services.carto.api_key')]];

        foreach ($catalogs as $name => $callback) {
            $props[$name] = new ContentKeyedOnceProp($name, $callback);
        }

        return $props;
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
            'ziggyContext' => fn () => [
                'url' => url('/'),
                'port' => Uri::of(url('/'))->port(),
                'defaults' => app('url')->getDefaultParameters(),
            ],
            // AdminLayout owns its title; Laravel Head's defaults belong to public pages.
            ...($onAdmin ? ['head' => fn () => []] : []),
            'auth' => is_null($user) ? null : [
                'can' => fn () => $onAdmin ? $this->getAllPermissionsMap($user) : ['index' => [], 'create' => [], 'forceDelete' => []],
                'user' => fn () => [
                    // Relations loaded on the request user by policies or controllers are not shared.
                    ...$user->withoutRelations()->toArray(),
                    'pronouns' => $onAdmin ? $user->getTranslations('pronouns') : $user->pronouns,
                    ...($onAdmin ? $this->getOpenTaskCounts($user) : []),
                    'isSuperAdmin' => $user->isSuperAdmin(),
                    'tenants' => $onAdmin
                        ? $user->tenants()->distinct()->get(['tenants.id', 'tenants.shortname', 'tenants.alias'])
                        : [],
                    ...($onAdmin ? $this->getNotificationPreview($user) : [
                        'unreadNotifications' => [],
                        'unreadNotificationsCount' => 0,
                    ]),
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
            'typesenseConfig' => TypesenseManager::getFrontendConfig(...),
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

    /** @return array{unreadNotifications: array, unreadNotificationsCount: int} */
    private function getNotificationPreview(User $user): array
    {
        $notifications = $user->unreadNotifications()
            ->limit(self::NOTIFICATION_PREVIEW_LIMIT)
            ->get(['id', 'type', 'data', 'created_at', 'read_at']);
        $count = $notifications->count() < self::NOTIFICATION_PREVIEW_LIMIT
            ? $notifications->count()
            : $user->unreadNotifications()->count();

        return [
            'unreadNotifications' => $notifications->map(function (DatabaseNotification $notification): array {
                $data = $notification->data;
                $preview = Arr::only($data, ['category', 'modelClass', 'title', 'body', 'excerpt', 'url', 'color', 'text', 'message']);

                foreach (['subject' => ['name', 'image'], 'object' => ['modelClass', 'name', 'url']] as $key => $fields) {
                    if (isset($data[$key]) && is_array($data[$key])) {
                        $preview[$key] = Arr::only($data[$key], $fields);
                    }
                }

                $action = $data['primaryAction'] ?? $data['actions'][0] ?? null;
                $preview['primaryAction'] = is_array($action) ? Arr::only($action, ['label', 'url']) : null;

                return [
                    ...$notification->only(['id', 'type', 'created_at', 'read_at']),
                    'data' => $preview,
                ];
            })->all(),
            'unreadNotificationsCount' => $count,
        ];
    }

    /**
     * Open and overdue task counts for the admin shell badges, in one query.
     *
     * @return array{tasks_count: int, overdue_tasks_count: int}
     */
    private function getOpenTaskCounts(User $user): array
    {
        $counts = Task::openTaskCountsFor($user);

        return [
            'tasks_count' => $counts['tasks_count'],
            'overdue_tasks_count' => $counts['overdue_tasks_count'],
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
                ->with(['primary_institution' => fn ($query) => $query->select('id', 'short_name')->with('media')])
                ->get(['id', 'alias', 'shortname', 'fullname', 'type', 'primary_institution_id'])
                ->each(function (Tenant $tenant): void {
                    $institution = $tenant->primary_institution;

                    if ($institution === null) {
                        return;
                    }

                    // The navigation reads these two keys; they come from the image's media.
                    $image = $institution->imageData('image');
                    $institution->setAttribute('image_url', $image['thumb'] ?? null);
                    $institution->setAttribute('image_focal_point', $image['focal_point'] ?? null);
                    $institution->unsetRelation('media');
                })
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
     * @return Collection<int, InstitutionType>
     */
    private function getInstitutionTypesForInertia(): Collection
    {
        return Cache::rememberForever(self::INSTITUTION_TYPES_CACHE_KEY,
            fn () => InstitutionType::query()->get(['id', 'title', 'slug', 'parent_id'])
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

    /** @return array{index: array<string, bool>, create: array<string, bool>, forceDelete: array<string, bool>} */
    private function getAllPermissionsMap(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::ALL_CACHE_PREFIX.$user->id, fn () => AuthorityCacheExpiry::for($user, 1800), function () use ($user): array {
            $builder = app(PermissionMapBuilder::class);

            return ['index' => $builder->indexMap($user), 'create' => $builder->createMap($user), 'forceDelete' => $builder->forceDeleteMap($user)];
        });
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
