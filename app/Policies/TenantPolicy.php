<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TENANT()->label);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function updateMainPage(User $user, Tenant $tenant): bool
    {
        $this->authorizer->forUser($user)->check('pages.update.padalinys');

        if ($this->authorizer->isAllScope) {
            return true;
        }

        $tenants = $this->authorizer->getPermissableDuties()->filter(function ($duty) {
            return $duty->hasPermissionTo('pages.update.padalinys');
        })->load('institution.tenant')->pluck('institution.tenant');

        // Check against tenant in the request
        return $tenants->contains($tenant);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            // Only allow deletion of pkp tenants
            if ($tenant->type == 'pkp') {
                return true;
            }
        }

        return false;
    }
}
