<?php

namespace App\Settings;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Settings;

/**
 * Settings for the Atstovavimas (Representation) dashboard feature.
 *
 * This class handles:
 * - **Institution Manager Role**: Configurable role that identifies student rep coordinators
 *   who can be contacted by student representatives for help/support
 * - **Visibility**: Delegates to ModelAuthorizer using permissions (institutions.read.padalinys, institutions.read.*)
 *
 * The institution visibility is now unified with the permission system:
 * - `institutions.read.*` → can see all institutions
 * - `institutions.read.padalinys` → can see institutions in authorized tenants
 * - `institutions.read.own` → can see institutions where user has duties + related institutions
 *
 * Cache Strategy:
 * - Manager role lookups are cached per-tenant
 * - Visibility uses ModelAuthorizer's built-in caching
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
     * The role ID that identifies institution managers (student rep coordinators).
     * Users with this role in a tenant can be contacted by student representatives.
     */
    public ?string $institution_manager_role_id = null;

    public static function group(): string
    {
        return 'atstovavimas';
    }

    /**
     * Get the institution manager role ID.
     */
    public function getInstitutionManagerRoleId(): ?string
    {
        return $this->institution_manager_role_id;
    }

    /**
     * Set the institution manager role ID.
     */
    public function setInstitutionManagerRoleId(?string $roleId): void
    {
        $this->institution_manager_role_id = $roleId;
    }

    /**
     * Get the institution manager role model.
     */
    public function getInstitutionManagerRole(): ?Role
    {
        if (! $this->institution_manager_role_id) {
            return null;
        }

        return Cache::remember(
            "atstovavimas:manager_role:{$this->institution_manager_role_id}",
            self::CACHE_TTL,
            fn () => Role::find($this->institution_manager_role_id)
        );
    }

    /**
     * Get the institution manager role name for display.
     */
    public function getInstitutionManagerRoleName(): ?string
    {
        return $this->getInstitutionManagerRole()?->name;
    }

    /**
     * Check if a user has the institution manager role.
     * Checks both direct user roles and roles assigned through duties.
     */
    public function userIsInstitutionManager(User $user): bool
    {
        $roleId = $this->getInstitutionManagerRoleId();

        if (! $roleId) {
            return false;
        }

        // Check direct user roles
        if ($user->roles()->where('id', $roleId)->exists()) {
            return true;
        }

        // Check roles through current duties
        return $user->current_duties()
            ->whereHas('roles', fn ($query) => $query->where('id', $roleId))
            ->exists();
    }

    /**
     * Get tenant IDs where the user has the institution manager role through duties.
     *
     * Results are cached per-user.
     */
    public function getManagerTenantIds(User $user): Collection
    {
        $roleId = $this->getInstitutionManagerRoleId();

        if (! $roleId) {
            return collect();
        }

        $cacheKey = self::getManagerTenantsCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $roleId) {
            return $user->current_duties()
                ->with(['roles', 'institution'])
                ->get()
                ->filter(fn ($duty) => $duty->roles->pluck('id')->contains($roleId))
                ->map(fn ($duty) => $duty->institution?->tenant_id)
                ->filter()
                ->unique()
                ->values();
        });
    }

    /**
     * Get visible tenant IDs for a user based on permissions.
     *
     * This uses the permission system via ModelAuthorizer:
     * - Super admins or users with institutions.read.* → all tenants
     * - Users with institutions.read.padalinys → their authorized tenants
     * - Others → empty (they only see their own institutions)
     */
    public function getVisibleTenantIds(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Tenant::query()
                ->where('type', '!=', 'pkp')
                ->pluck('id');
        }

        $authorizer = app(ModelAuthorizer::class)->forUser($user);

        // Check for global read permission
        if ($authorizer->check('institutions.read.*')) {
            return Tenant::query()
                ->where('type', '!=', 'pkp')
                ->pluck('id');
        }

        // Check for padalinys-level permission
        if ($authorizer->check('institutions.read.padalinys')) {
            $tenants = $authorizer->getTenants('institutions.read.padalinys');

            return $tenants
                ->where('type', '!=', 'pkp')
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();
        }

        return collect();
    }

    /**
     * Get the cache key for a user's manager tenant IDs.
     */
    public static function getManagerTenantsCacheKey(string $userId): string
    {
        return "atstovavimas:manager_tenants:{$userId}";
    }

    /**
     * Clear the manager tenants cache for a specific user.
     */
    public static function clearManagerCache(string $userId): void
    {
        Cache::forget(self::getManagerTenantsCacheKey($userId));
    }

    /**
     * Clear the manager cache for all users associated with a duty.
     */
    public static function clearManagerCacheForDuty(\App\Models\Duty $duty): void
    {
        $duty->load('users');
        foreach ($duty->users as $user) {
            self::clearManagerCache($user->id);
        }
    }

    /**
     * Clear the manager role cache when the role setting changes.
     */
    public static function clearManagerRoleCache(?string $roleId): void
    {
        if ($roleId) {
            Cache::forget("atstovavimas:manager_role:{$roleId}");
        }
    }

    /**
     * Get tenant visibility role IDs.
     * These are roles that grant visibility to institutions within specific tenants.
     *
     * Currently returns empty collection - can be configured via settings if needed.
     */
    public function getTenantVisibilityRoleIds(): Collection
    {
        // Currently not configured - the institution manager role handles tenant visibility
        // This could be extended to include additional visibility roles if needed
        return collect();
    }

    /**
     * Get global visibility role IDs.
     * These are roles that grant visibility to all institutions globally.
     *
     * Currently returns empty collection - super admin check handles this case.
     */
    public function getGlobalVisibilityRoleIds(): Collection
    {
        // Currently not configured - super admin role handles global visibility
        // This could be extended to include additional global visibility roles if needed
        return collect();
    }
}
