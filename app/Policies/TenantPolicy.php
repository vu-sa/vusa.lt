<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Enums\TenantType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TENANT->label());
    }

    /**
     * Determine whether the user can create models.
     */
    #[\Override]
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view any models.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    #[\Override]
    public function view(User $user, Model $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    #[\Override]
    public function update(User $user, Model $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function updateMainPage(User $user, Tenant $tenant): bool
    {
        return $this->authorizer->scope($user, 'pages.update.padalinys')->allowsTenant($tenant);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  Tenant  $tenant
     */
    #[\Override]
    public function delete(User $user, Model $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            // Only allow deletion of pkp tenants
            if ($tenant->type === TenantType::Pkp) {
                return true;
            }
        }

        return false;
    }
}
