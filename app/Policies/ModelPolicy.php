<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ModelPolicy
{
    protected $pluralModelName;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.create.padalinys');
    }

    public function restore(User $user, Model $model)
    {
        if ($this->commonChecker($user, $model, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * commonChecker
     * This function checks resource models by a common pattern. First, it checks for wildcard,
     * then if the user has permission to view own models, and finally if the user has permission
     * to view models of the same tenant.
     *
     * The implementation may be finicky.
     */
    protected function commonChecker(User $user, Model $model, string $ability, string $relationFromDuties, $hasManyTenants = true): bool
    {

        // Check for wildcard (.*), if true, return true
        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::ALL()->label)) {
            return true;
        }

        // Check for .padalinys
        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::OWN()->label)) {

            // Since a user can have multiple duties, we need to get all of them.
            // ModelAuthorizer has already taken care of getting the permissable duties.

            $permissableDuties = $this->authorizer->getPermissableDuties();

            if ($relationFromDuties === 'duties') {
                $permissableModels = $permissableDuties;
            } else {
                $permissableModels = $permissableDuties->load($relationFromDuties)->pluck($relationFromDuties)->flatten();
            }

            if ($permissableModels->contains('id', $model->id)) {
                return true;
            }

            // only works for institutions for now
            // check model relations if institution
            // TODO
            if ($this->pluralModelName === 'institutions') {
                $institutions = new Collection(RelationshipService::getRelatedInstitutions($model));

                if ($institutions->isEmpty()) {
                    return false;
                }

                // check if any element exists in relations array, then return true
                // relations array consists of 4 collections of models, so we need to flatten it
                if ($institutions->intersect((new Collection($permissableModels)))->isNotEmpty()) {
                    return true;
                }
            }
        }

        $tenantRelation = $hasManyTenants ? 'tenants' : 'tenant';

        // If the user has permission to view tenant and user belongs to same tenant as the institution
        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::PADALINYS()->label)) {
            $permissableTenants = $user->tenants()
                ->whereIn('duties.id', $this->authorizer->getPermissableDuties()
                    ->pluck('id'))
                ->get();

            $modelTenants = $model->load($tenantRelation)->$tenantRelation;

            $modelCollection = new Collection;

            if ($modelTenants instanceof Model) {
                $modelCollection->push($modelTenants);
            }

            if ($modelTenants instanceof Collection) {
                $modelCollection = $modelTenants;
            }

            // final intersection check
            if ($modelCollection->intersect($permissableTenants)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
}
