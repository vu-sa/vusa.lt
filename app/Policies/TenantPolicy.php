<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class TenantPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        $this->pluralModelName = Str::plural(ModelEnum::INSTITUTION()->label);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            // Only allow deletion of pkp tenants
            if ($tenant->type == 'pkp') {
                return true;
            }
        }

        return false;
    }
}
