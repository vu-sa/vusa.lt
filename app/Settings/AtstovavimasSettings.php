<?php

namespace App\Settings;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Settings;

/**
 * Settings for the Atstovavimas (Representation) dashboard feature.
 *
 * This class handles **dashboard visibility rules** - determining which institutions
 * a user can see in the Atstovavimas dashboard. This is separate from and complementary
 * to the ModelAuthorizer/ModelPolicy system:
 *
 * - **ModelPolicy + HasCommonChecks**: Gates CRUD operations ("Can user X create/read/update/delete model Z?")
 * - **ModelAuthorizer**: Handles permission checking and tenant filtering for queries
 * - **AtstovavimasSettings**: Configurable visibility rules ("What institutions does user X see in dashboard?")
 *
 * The key difference is that coordinator roles are **admin-configurable** business rules,
 * while permissions are developer-defined access controls. A user might have permission
 * to read institutions (via InstitutionPolicy) but only see a subset in the dashboard
 * based on their coordinator role status.
 *
 * Cache Strategy:
 * - Coordinator tenant IDs are cached per-user with key "atstovavimas:coordinator_tenants:{userId}"
 * - Cache is invalidated when duties or roles are updated (via model observers)
 * - TTL: 1 hour (3600 seconds)
 *
 * @see \App\Services\ModelAuthorizer for permission-based authorization
 * @see \App\Policies\Traits\HasCommonChecks for policy authorization patterns
 */
class AtstovavimasSettings extends Settings
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Array of role IDs that grant global tenant visibility in Atstovavimas dashboard.
     * Users with any of these roles can see all tenants in the tenant tab.
     *
     * @var string[]
     */
    public array $global_visibility_role_ids = [];

    /**
     * Array of role IDs that grant tenant visibility only for tenants where the
     * user holds a current duty with one of these roles.
     *
     * @var string[]
     */
    public array $tenant_visibility_role_ids = [];

    public static function group(): string
    {
        return 'atstovavimas';
    }

    public function getGlobalVisibilityRoleIds(): Collection
    {
        return collect($this->global_visibility_role_ids)
            ->map(fn ($id) => (string) $id)
            ->filter();
    }

    public function getTenantVisibilityRoleIds(): Collection
    {
        $tenantVisibilityRoleIds = collect($this->tenant_visibility_role_ids)
            ->map(fn ($id) => (string) $id)
            ->filter();

        return $tenantVisibilityRoleIds;
    }

    public function setGlobalVisibilityRoleIds(array $ids): void
    {
        $this->global_visibility_role_ids = array_map('strval', array_filter($ids));
    }

    public function setTenantVisibilityRoleIds(array $ids): void
    {
        $this->tenant_visibility_role_ids = array_map('strval', array_filter($ids));
    }

    /**
     * Check if a user has any of the coordinator roles.
     * This checks both direct user roles and roles assigned through duties.
     */
    public function userHasCoordinatorRole(\App\Models\User $user): bool
    {
        return $this->getCoordinatorTenantIds($user)->isNotEmpty();
    }

    /**
     * Get the tenant IDs where the user has coordinator access.
     * This returns only tenants where the user has a coordinator role through their duties.
     *
     * Results are cached per-user. Use clearCoordinatorCache() to invalidate.
     */
    public function getCoordinatorTenantIds(\App\Models\User $user): Collection
    {
        $coordinatorRoleIds = $this->getTenantVisibilityRoleIds();

        if ($coordinatorRoleIds->isEmpty()) {
            return collect();
        }

        $cacheKey = self::getTenantVisibilityCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $coordinatorRoleIds) {
            // Get duties that have coordinator roles, along with their institution's tenant
            $coordinatorTenantIds = $user->current_duties()
                ->with(['roles', 'institution'])
                ->get()
                ->filter(function ($duty) use ($coordinatorRoleIds) {
                    // Check if this duty has any of the coordinator roles
                    return $duty->roles->pluck('id')->intersect($coordinatorRoleIds)->isNotEmpty();
                })
                ->map(fn ($duty) => $duty->institution?->tenant_id)
                ->filter()
                ->unique()
                ->values();

            return $coordinatorTenantIds;
        });
    }

    /**
     * Get the cache key for coordinator tenant IDs
     */
    public static function getCoordinatorCacheKey(string $userId): string
    {
        return self::getTenantVisibilityCacheKey($userId);
    }

    public static function getTenantVisibilityCacheKey(string $userId): string
    {
        return "atstovavimas:tenant_visibility_tenants:{$userId}";
    }

    /**
     * Clear the coordinator cache for a specific user
     */
    public static function clearCoordinatorCache(string $userId): void
    {
        Cache::forget(self::getTenantVisibilityCacheKey($userId));
    }

    /**
     * Clear the coordinator cache for all users associated with a duty
     */
    public static function clearCoordinatorCacheForDuty(\App\Models\Duty $duty): void
    {
        $duty->load('users');
        foreach ($duty->users as $user) {
            self::clearCoordinatorCache($user->id);
        }
    }

    /**
     * Get the names of coordinator roles for display purposes
     */
    public function getCoordinatorRoleNames(): Collection
    {
        $roleIds = $this->getTenantVisibilityRoleIds();

        if ($roleIds->isEmpty()) {
            return collect();
        }

        return Role::whereIn('id', $roleIds->toArray())->pluck('name');
    }

    public function userHasGlobalVisibilityRole(\App\Models\User $user): bool
    {
        $roleIds = $this->getGlobalVisibilityRoleIds();

        if ($roleIds->isEmpty()) {
            return false;
        }

        if ($user->roles()->whereIn('id', $roleIds)->exists()) {
            return true;
        }

        return $user->current_duties()
            ->whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('id', $roleIds);
            })
            ->exists();
    }

    public function getVisibleTenantIds(\App\Models\User $user): Collection
    {
        if ($user->isSuperAdmin() || $this->userHasGlobalVisibilityRole($user)) {
            return Tenant::query()
                ->where('type', '!=', 'pkp')
                ->pluck('id');
        }

        $tenantRoleIds = $this->getTenantVisibilityRoleIds();

        if ($tenantRoleIds->isEmpty()) {
            return collect();
        }

        return $this->getCoordinatorTenantIds($user)
            ->filter()
            ->values();
    }
}
