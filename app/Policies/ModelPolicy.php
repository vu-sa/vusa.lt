<?php

namespace App\Policies;

use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ModelPolicy
{
    protected $pluralModelName;

    protected $authorizer;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Authorizer $authorizer): bool
    {
        return $authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.create.padalinys');
    }

    /**
     * commonChecker
     * This function checks resource models by a common pattern. First, it checks for wildcard,
     * then if the user has permission to view own models, and finally if the user has permission
     * to view models of the same padalinys.
     *
     * The implementation may be finicky.
     */
    protected function commonChecker(User $user, Model $model, string $ability, string $relationFromDuties, $hasManyPadalinys = true): bool
    {
        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::ALL()->label)) {
            return true;
        }

        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::OWN()->label)) {
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

                // check if any element exists in relations array, then return true
                // relations array consists of 4 collections of models, so we need to flatten it
                if ($institutions->intersect((new Collection($permissableModels)))->isNotEmpty()) {
                    return true;
                }
            }
        }

        $padalinysRelation = $hasManyPadalinys ? 'padaliniai' : 'padalinys';

        // If the user has permission to view padalinys and user belongs to same padalinys as the institution
        if ($this->authorizer->forUser($user)->check($this->pluralModelName.'.'.$ability.'.'.PermissionScopeEnum::PADALINYS()->label)) {
            $permissablePadaliniai = $user->padaliniai()
                ->whereIn('duties.id', $this->authorizer->getPermissableDuties()
                ->pluck('id'))
                ->get();

            $modelPadaliniai = $model->load($padalinysRelation)->$padalinysRelation;

            $modelCollection = new Collection();

            if ($modelPadaliniai instanceof Model) {
                $modelCollection->push($modelPadaliniai);
            }

            if ($modelPadaliniai instanceof Collection) {
                $modelCollection = $modelPadaliniai;
            }

            // final intersection check
            if ($modelCollection->intersect($permissablePadaliniai)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
}
