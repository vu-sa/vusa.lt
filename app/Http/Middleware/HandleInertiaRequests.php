<?php

namespace App\Http\Middleware;

use App\Models\Form;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use App\Services\Typesense\TypesenseManager;
use App\Settings\FormSettings;
use App\Settings\SiteSettings;
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
    #[\Override]
    protected $rootView = 'app';

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
        $user = $this->getLoggedInUserForInertia();

        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

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
                'privacyPageUrl' => app(SiteSettings::class)->privacyPageUrl(),
            ],
            'auth' => is_null($user) ? null : [
                'can' => fn () => [
                    'index' => fn () => $this->getIndexPermissions($user),
                    'create' => fn () => $this->getCreatePermissions($user),
                    'forceDelete' => fn () => $this->getForceDeletePermissions($user),
                    'manageSettings' => fn () => $user->can('manage-settings'),
                    'accessAdministration' => fn () => $user->can('access-administration'),
                ],
                'user' => fn () => [
                    ...$user->toArray(),
                    'isSuperAdmin' => $isSuperAdmin,
                    'tenants' => $user->tenants()->get(['tenants.id', 'tenants.shortname', 'tenants.alias'])->unique(),
                    'unreadNotifications' => $user->unreadNotifications()->get(),
                    'tutorial_progress' => $user->tutorial_progress ?? [],
                    'ui_preferences' => $user->ui_preferences ?? [],
                ],
                'impersonating' => fn () => $this->getImpersonationState($request),
                'registrationForms' => fn () => $this->getViewableRegistrationForms($user),
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
            'typesenseConfig' => TypesenseManager::getFrontendConfig(...),
            'pwa' => [
                'vapidPublicKey' => fn () => config('webpush.vapid.public_key'),
                'hasPushSubscription' => fn () => $user?->pushSubscriptions()->exists() ?? false,
                'subscriptionEndpoints' => fn () => $user?->pushSubscriptions()
                    ->pluck('endpoint')
                    ->toArray() ?? [],
            ],
        ]);
    }

    private function getLoggedInUserForInertia(): ?User
    {
        $user = User::query()
            ->withCount(['tasks' => function ($query): void {
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
        return Cache::remember(PermissionMapBuilder::INDEX_CACHE_PREFIX.$user->id, 1800,
            fn () => app(PermissionMapBuilder::class)->indexMap($user)
        );
    }

    /**
     * @return array<string, bool>
     */
    private function getCreatePermissions(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::CREATE_CACHE_PREFIX.$user->id, 1800,
            fn () => app(PermissionMapBuilder::class)->createMap($user)
        );
    }

    /**
     * @return array<string, bool>
     */
    private function getForceDeletePermissions(User $user): array
    {
        return Cache::remember(PermissionMapBuilder::FORCE_DELETE_CACHE_PREFIX.$user->id, 1800,
            fn () => app(PermissionMapBuilder::class)->forceDeleteMap($user)
        );
    }

    /**
     * The member and student rep registration forms, but only when this user may open them.
     *
     * The sidebar links straight to these two forms, so the ids are filtered through the
     * policy here — a link is never shown for a form that would then respond with 403.
     *
     * @return array{member: string|null, studentRep: string|null}
     */
    private function getViewableRegistrationForms(User $user): array
    {
        return Cache::remember(self::registrationFormsCacheKey($user->id), 1800, function () use ($user) {
            $settings = app(FormSettings::class);

            return [
                'member' => $this->formIdIfViewable($user, $settings->member_registration_form_id),
                'studentRep' => $this->formIdIfViewable($user, $settings->student_rep_registration_form_id),
            ];
        });
    }

    private function formIdIfViewable(User $user, ?string $formId): ?string
    {
        if (! $formId) {
            return null;
        }

        $form = Form::find($formId);

        return $form && $user->can('view', $form) ? $form->id : null;
    }

    public static function registrationFormsCacheKey(string $userId): string
    {
        return 'registration-forms-'.$userId;
    }
}
