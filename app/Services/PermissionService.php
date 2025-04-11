<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Service class for handling permission checks with a clean interface
 */
class PermissionService
{
    /**
     * Constructor injection of ModelAuthorizer
     */
    public function __construct(protected ModelAuthorizer $authorizer) {}

    /**
     * Check if the user has the specified permission
     *
     * @param  string  $permission  Permission string in format "resource.action.scope"
     * @param  User|null  $user  User to check, or null for current authenticated user
     */
    public function check(string $permission, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (! $user) {
            return false;
        }

        return $this->authorizer->forUser($user)->check($permission);
    }

    /**
     * Check if the user has permission for a specific resource, action and scope
     *
     * @param  string  $resource  The resource name (e.g., "posts")
     * @param  string  $action  The action name (e.g., "read")
     * @param  string  $scope  The scope (e.g., "all", "own", "padalinys")
     * @param  User|null  $user  User to check, or null for current authenticated user
     */
    public function checkScope(string $resource, string $action, string $scope, ?User $user = null): bool
    {
        $permission = "{$resource}.{$action}.{$scope}";

        return $this->check($permission, $user);
    }

    /**
     * Check if the user is a super admin
     *
     * @param  User|null  $user  User to check, or null for current authenticated user
     */
    public function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(config('permission.super_admin_role_name'));
    }

    /**
     * Get available tenants for the specified user based on their permissions
     *
     * @param  User|null  $user  User to check, or null for current authenticated user
     * @param  string|null  $permission  Optional permission to filter tenants by
     */
    public function getTenants(?User $user = null, ?string $permission = null): Collection
    {
        $user = $user ?: Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $this->authorizer->forUser($user)->getTenants($permission);
    }

    /**
     * Reset permission cache for the specified user
     *
     * @param  User|int|string  $user  User instance or user ID
     */
    public function resetCache($user): void
    {
        $this->authorizer->resetCache($user);
    }
}
