<?php

namespace App\Policies\Traits;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property-read ModelAuthorizer $authorizer Injected by ModelPolicy, the trait's only consumer.
 */
trait HasCommonChecks
{
    /**
     * Whether the model relates to tenants through a `tenants` (many) relation.
     *
     * Policies whose model belongs to a single tenant (a `tenant` relation) must set
     * this to false, mirroring the last argument they already pass to commonChecker().
     * Getting this wrong throws RelationNotFoundException at runtime, because
     * commonChecker() eager-loads the relation by name.
     */
    protected bool $hasManyTenants = true;

    /**
     * Common checker logic for tenant-based authorization.
     *
     * @param  User  $user  The user to check permissions for
     * @param  Model  $model  The model to check against
     * @param  string  $ability  The CRUD action being checked (e.g., read, update)
     * @param  string|null  $resourceName  Override the resource name if different from plural model name
     * @param  bool  $hasManyTenants  Whether the model can belong to multiple tenants
     */
    protected function commonChecker(
        User $user,
        Model $model,
        string $ability,
        ?string $resourceName = null,
        bool $hasManyTenants = true
    ): bool {
        // WARNING: Uses the current object pluralModelName, it must be set, or a resource name must be provided
        $resource = $resourceName ?? $this->pluralModelName;

        if (empty($resource)) {
            Log::error('Resource name is not set in the policy. Please provide a resource name.');

            return false;
        }

        $permissionBase = $resource.'.'.$ability.'.';

        return $this->authorizer->allows($user, $permissionBase.PermissionScopeEnum::ALL->label())
            || $this->allowsOwnScope($user, $model, $permissionBase, $resource)
            || $this->allowsPadalinysScope($user, $model, $permissionBase, $hasManyTenants);
    }

    /**
     * Whether one of the user's duties is directly associated with the model.
     */
    private function allowsOwnScope(User $user, Model $model, string $permissionBase, string $resource): bool
    {
        $ownPermission = $permissionBase.PermissionScopeEnum::OWN->label();
        $ownScope = $this->authorizer->scope($user, $ownPermission);

        if (! $ownScope->granted) {
            return false;
        }

        $permissableDuties = $ownScope->duties;

        return $this->authorizer->ownModelIds($user, $ownPermission, $permissableDuties, $resource)->contains($model->getKey());
    }

    /**
     * Whether the model belongs to one of the tenants the user holds the padalinys scope in.
     */
    private function allowsPadalinysScope(User $user, Model $model, string $permissionBase, bool $hasManyTenants): bool
    {
        $tenantRelation = $hasManyTenants ? 'tenants' : 'tenant';

        // Globally scoped models (tags, types, categories, navigation) have no tenant
        // relation and are only ever granted "*"; deny rather than 500 on loading it.
        if (! method_exists($model, $tenantRelation)) {
            return false;
        }

        // Scoped by non-ended duties only. Never resolve this from $user->tenants(), a
        // HasManyDeep over every duty ever held — an ended duty would grant access here.
        $padalinysScope = $this->authorizer->scope($user, $permissionBase.PermissionScopeEnum::PADALINYS->label());

        if (! $padalinysScope->granted) {
            return false;
        }

        $modelTenants = $model->loadMissing($tenantRelation)->getRelation($tenantRelation);

        $modelCollection = match (true) {
            $modelTenants instanceof Collection => $modelTenants,
            $modelTenants instanceof Model => new Collection([$modelTenants]),
            default => new Collection,
        };

        return $modelCollection->intersect($padalinysScope->tenants)->isNotEmpty();
    }

    /**
     * Common view any check for standard index pages
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, $this->pluralModelName.'.'.CRUDEnum::READ->label().'.padalinys');
    }

    /**
     * Common create check for standard resource creation
     */
    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, $this->pluralModelName.'.'.CRUDEnum::CREATE->label().'.padalinys');
    }

    /**
     * Common restore check for standard soft delete restoration.
     *
     * Restoring is the inverse of deleting, so it deliberately reuses the model's
     * existing `{resource}.delete.{scope}` permission rather than introducing one
     * of its own.
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::DELETE->label(), $this->pluralModelName, $this->hasManyTenants);
    }
}
