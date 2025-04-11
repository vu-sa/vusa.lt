<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ModelAuthorizer
{
    /**
     * The ModelAuthorizer service handles authorization checks against users and their duties.
     * 
     * It provides methods to check if a user has permissions through:
     * - Direct user permissions
     * - Duties with associated roles and permissions
     * - Tenant-specific permissions
     */

    public User $user;

    public Collection $duties;

    public Collection $permissableDuties;

    public bool $isAllScope = false;

    /**
     * Last permission that was checked with checkAllRoleables
     * This is used to retain context when getTenants is called without specifying a permission
     */
    protected ?string $lastCheckedPermission = null;

    /**
     * Cache TTL in seconds (12 hours)
     */
    protected const CACHE_TTL = 43200;

    public function __construct()
    {
        $this->duties = new Collection;
        $this->permissableDuties = new Collection;
    }

    public function getPermissableDuties(): Collection
    {
        return $this->permissableDuties;
    }

    /**
     * Set the user for subsequent permission checks.
     *
     * @param User $user The user to check permissions for
     * @return self
     */
    public function forUser(User $user): self
    {
        if (! isset($this->user) || $this->user->id !== $user->id) {
            $this->user = $user;
            $this->duties = new Collection;
            $this->isAllScope = false;
            $this->lastCheckedPermission = null;
        }

        return $this;
    }

    /**
     * Check all roles and duties for the given permission.
     *
     * @param string $permission Permission to check in format "resource.action.scope"
     * @return bool Whether the user has the permission
     */
    public function checkAllRoleables(string $permission): bool
    {
        $this->permissableDuties = new Collection;
        $this->lastCheckedPermission = $permission;
        
        // Generate cache key based on user and permission
        $cacheKey = "auth:permissions:{$this->user->id}:{$permission}";
        
        // TODO: reenable cache in the future, but needs to be fixed, because the values sometimes are not returned properly
        // return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($permission) {
            // Super admin check
            if ($this->user->isSuperAdmin()) {
                $this->isAllScope = true;
                return true;
            }

            // Direct user permission check
            if ($this->user->hasPermissionTo($permission)) {
                return true;
            }

            $this->loadDuties();

            $result = false;

            foreach ($this->duties as $duty) {
                if ($duty->hasPermissionTo($permission)) {
                    $this->permissableDuties->push($duty);
                    
                    // Check if this permission has global scope
                    if ($this->hasGlobalPermission($duty, $permission)) {
                        $this->isAllScope = true;
                    }
                    
                    $result = true;
                }
            }

            return $result;
        // });
    }

    /**
     * Alias for checkAllRoleables
     */
    public function check(string $permission): bool
    {
        return $this->checkAllRoleables($permission);
    }

    /**
     * Load user duties with necessary relations
     */
    protected function loadDuties(): Collection
    {
        if ($this->duties->isEmpty()) {
            $cacheKey = "auth:duties:{$this->user->id}";
            
            $this->duties = Cache::remember($cacheKey, static::CACHE_TTL, function () {
                return $this->user->load([
                    'current_duties:id,name,institution_id', 
                    'current_duties.institution:id', 
                    'current_duties.roles.permissions'
                ])->current_duties;
            });
        }

        return $this->duties;
    }

    /**
     * Get tenants from permissible duties based on a specific permission.
     * If no permission is provided but a permission was previously checked,
     * that permission will be used as context.
     *
     * @param string|null $permission Optional permission to filter duties by
     * @return Collection<Tenant>
     */
    public function getTenants(?string $permission = null): Collection
    {
        // Use the provided permission or fall back to the last checked one
        $effectivePermission = $permission ?? $this->lastCheckedPermission;
 
        // Generate a specific cache key based on the effective permission
        $permissionSuffix = $effectivePermission ? ":{$effectivePermission}" : "";
        $cacheKey = "auth:tenants:{$this->user->id}{$permissionSuffix}";

        // TODO: reenable cache in the future, but needs to be fixed, because the values sometimes are not returned properly
        // return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($effectivePermission) {
            // Super admin has access to all tenants
            if ($this->user->isSuperAdmin() || $this->isAllScope) {
                return Tenant::all();
            }
            
            // If a specific permission is provided, filter duties by that permission
            if ($effectivePermission) {
                $this->checkAllRoleables($effectivePermission);
            }
            
            // If no specific permission, or no permissible duties found, use all current duties
            $dutiesToUse = $this->permissableDuties->isNotEmpty() 
                ? $this->permissableDuties 
                : $this->loadDuties();
            
            return new Collection($dutiesToUse
                ->load('institution.tenant')
                ->pluck('institution.tenant')
                ->flatten(1)
                ->unique('id')
                ->values());
        // });
    }

    /**
     * Check if the duty has a global permission.
     * Global permissions use "*" as scope (e.g., "resource.action.*")
     * 
     * @param mixed $duty The duty to check
     * @param string $permission Permission string in format "resource.action.scope"
     * @return bool
     */
    protected function hasGlobalPermission($duty, string $permission): bool
    {
        $permParts = explode('.', $permission);
        
        // Check for malformed permission string
        if (count($permParts) < 3) {
            return false;
        }
        
        $permParts[2] = '*';
        $globalPermVariant = implode('.', $permParts);

        return $duty->hasPermissionTo($globalPermVariant);
    }
    
    /**
     * Reset the internal cache for a specific user
     * 
     * @param User|int|string $user User instance or user ID
     * @return void
     */
    public function resetCache($user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        Cache::forget("auth:duties:{$userId}");
        
        // Clear tenant caches with all possible permission suffixes
        foreach (Cache::get("auth:permission_keys:{$userId}", []) as $key) {
            if (strpos($key, "auth:permissions:{$userId}:") === 0) {
                $permission = substr($key, strlen("auth:permissions:{$userId}:"));
                Cache::forget("auth:tenants:{$userId}:{$permission}");
            }
        }
        Cache::forget("auth:tenants:{$userId}");
        
        // Clear all permission checks for this user
        $keys = Cache::get("auth:permission_keys:{$userId}", []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget("auth:permission_keys:{$userId}");
    }
}
