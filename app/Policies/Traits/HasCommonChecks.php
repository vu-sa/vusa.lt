<?php

namespace App\Policies\Traits;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionRelationService;
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
        $authorizer = $this->authorizer;

        // WARNING: Uses the current object pluralModelName, it must be set, or a resource name must be provided
        $resource = $resourceName ?? $this->pluralModelName;

        if (empty($resource)) {
            Log::error('Resource name is not set in the policy. Please provide a resource name.');

            return false;
        }

        // Build the permission string using model name and action
        $permissionBase = $resource.'.'.$ability.'.';

        // Check for wildcard (.*) - all-access permission
        if ($authorizer->allows($user, $permissionBase.PermissionScopeEnum::ALL->label())) {
            return true;
        }

        // Check for "own" scope - user's duties directly associated with the model
        $ownPermission = $permissionBase.PermissionScopeEnum::OWN->label();
        $ownScope = $authorizer->scope($user, $ownPermission);

        if ($ownScope->granted) {
            $permissableDuties = $ownScope->duties;
            $relationFromDuties = $resource;

            $allowedIds = $authorizer->ownModelIds($user, $ownPermission, $permissableDuties, $relationFromDuties);

            // Check for direct relationship
            if ($allowedIds->contains($model->getKey())) {
                return true;
            }

            // An institution is also "own" when one of the user's institutions is authorized to see it.
            if ($resource === 'institutions' && $model instanceof Institution) {
                $userInstitutionIds = $permissableDuties->pluck('institution_id')->filter()->map(fn ($id) => (string) $id);

                if (app(InstitutionRelationService::class)->authorizedIdsFor($userInstitutionIds)->contains((string) $model->getKey())) {
                    return true;
                }
            }
        }

        // Check for padalinys (tenant) scope - models belonging to user's tenants
        $tenantRelation = $hasManyTenants ? 'tenants' : 'tenant';

        // Globally scoped models (tags, types, categories, navigation) have no tenant
        // relation at all. They are only ever granted the "*" scope, so this branch is
        // normally unreachable — but loading a missing relation would throw a 500 rather
        // than simply denying, so deny explicitly instead.
        if (! method_exists($model, $tenantRelation)) {
            return false;
        }

        // Scoped by non-ended duties only. Never resolve this from $user->tenants(), a
        // HasManyDeep relation that includes every duty the user has ever held — an ended
        // duty would grant padalinys-scope access through this branch.
        $padalinysScope = $authorizer->scope($user, $permissionBase.PermissionScopeEnum::PADALINYS->label());

        if ($padalinysScope->granted) {
            $permissableTenants = $padalinysScope->tenants;

            $modelTenants = $model->loadMissing($tenantRelation)->getRelation($tenantRelation);

            // Convert to collection for consistent handling
            $modelCollection = new Collection;
            if ($modelTenants instanceof Model) {
                $modelCollection->push($modelTenants);
            } elseif ($modelTenants instanceof Collection) {
                $modelCollection = $modelTenants;
            }

            // Check if any tenant matches
            if ($modelCollection->intersect($permissableTenants)->isNotEmpty()) {
                return true;
            }
        }

        return false;
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
