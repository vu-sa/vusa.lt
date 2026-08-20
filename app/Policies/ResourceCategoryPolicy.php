<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\ResourceCategory;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

/**
 * Resource categories have no permissions of their own — they are not in ModelEnum, so
 * `resourceCategories.*` is never seeded and inventing it here would lock out everyone but
 * super admins. Instead each ability maps onto the matching ability on the resources the
 * category groups, which is what ResourceCategoryController was already reaching for (it just
 * used `create` for edit and destroy too).
 *
 * The table carries no tenant_id — categories are a global taxonomy — so there is no tenant
 * scoping to apply and the `own` scope is meaningless. Both the padalinys and the global scope
 * are accepted, matching how every seeded role that can create resources can also update and
 * delete them.
 */
class ResourceCategoryPolicy
{
    public function __construct(protected ModelAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->checkResourceAbility($user, CRUDEnum::READ->label());
    }

    public function view(User $user, ResourceCategory $resourceCategory): bool
    {
        return $this->checkResourceAbility($user, CRUDEnum::READ->label());
    }

    public function create(User $user): bool
    {
        return $this->checkResourceAbility($user, CRUDEnum::CREATE->label());
    }

    public function update(User $user, ResourceCategory $resourceCategory): bool
    {
        return $this->checkResourceAbility($user, CRUDEnum::UPDATE->label());
    }

    public function delete(User $user, ResourceCategory $resourceCategory): bool
    {
        return $this->checkResourceAbility($user, CRUDEnum::DELETE->label());
    }

    /**
     * ModelAuthorizer::check() matches one exact permission string, so both scopes are tried.
     */
    private function checkResourceAbility(User $user, string $ability): bool
    {
        $base = Str::plural(ModelEnum::RESOURCE->label()).'.'.$ability.'.';
        $authorizer = $this->authorizer->forUser($user);

        return $authorizer->check($base.PermissionScopeEnum::ALL->label())
            || $authorizer->check($base.PermissionScopeEnum::PADALINYS->label());
    }
}
