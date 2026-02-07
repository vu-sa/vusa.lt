<?php

namespace App\Policies\Traits;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait HasCommonChecks
{
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
        $authorizer = app(ModelAuthorizer::class);

        // WARNING: Uses the current object pluralModelName, it must be set, or a resource name must be provided
        $resource = $resourceName ?? $this->pluralModelName;

        if (empty($resource)) {
            Log::error('Resource name is not set in the policy. Please provide a resource name.');

            return false;
        }

        // Build the permission string using model name and action
        $permissionBase = $resource.'.'.$ability.'.';

        // Check for wildcard (.*) - all-access permission
        if ($authorizer->forUser($user)->check($permissionBase.PermissionScopeEnum::ALL()->label)) {
            return true;
        }

        // Check for "own" scope - user's duties directly associated with the model
        if ($authorizer->forUser($user)->check($permissionBase.PermissionScopeEnum::OWN()->label)) {
            $permissableDuties = $authorizer->getPermissableDuties();
            $relationFromDuties = $resource;

            if ($resource === 'duties') {
                $permissableModels = $permissableDuties;
            } else {
                $permissableModels = $permissableDuties->load($relationFromDuties)
                    ->pluck($relationFromDuties)
                    ->flatten()
                    ->filter(); // Remove null values from duties without the related model
            }

            // Check for direct relationship
            if ($permissableModels->contains('id', $model->getKey())) {
                return true;
            }

            // Check related institutions (for institution model)
            // User can access an institution if any of their own institutions have an
            // authorized relationship TO the target institution
            if ($resource === 'institutions' && $model instanceof \App\Models\Institution) {
                // For each of the user's institutions, check if the target institution
                // is in their authorized related institutions
                foreach ($permissableModels as $userInstitution) {
                    if ($userInstitution instanceof \App\Models\Institution) {
                        $authorizedRelated = RelationshipService::getRelatedInstitutions($userInstitution, authorizedOnly: true);
                        if ($authorizedRelated->contains('id', $model->getKey())) {
                            return true;
                        }
                    }
                }
            }
        }

        // Check for padalinys (tenant) scope - models belonging to user's tenants
        $tenantRelation = $hasManyTenants ? 'tenants' : 'tenant';

        if ($authorizer->forUser($user)->check($permissionBase.PermissionScopeEnum::PADALINYS()->label)) {
            $permissableTenants = $user->tenants()
                ->whereIn('duties.id', $authorizer->getPermissableDuties()->pluck('id'))
                ->get();

            $modelTenants = $model->load($tenantRelation)->getRelation($tenantRelation);

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
        return app(ModelAuthorizer::class)->forUser($user)->check($this->pluralModelName.'.'.CRUDEnum::READ()->label.'.padalinys');
    }

    /**
     * Common create check for standard resource creation
     */
    public function create(User $user): bool
    {
        return app(ModelAuthorizer::class)->forUser($user)->check($this->pluralModelName.'.'.CRUDEnum::CREATE()->label.'.padalinys');
    }

    /**
     * Common restore check for standard soft delete restoration
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::DELETE()->label);
    }
}
