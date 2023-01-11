<?php

namespace App\Policies;

use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ModelPolicy {

    protected $pluralModelName;
    protected $authorizer;
    
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Authorizer $authorizer): bool
    {      
        return $authorizer->forUser($user)->check($this->pluralModelName . '.read.padalinys');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        return $this->authorizer->forUser($user)->check($this->pluralModelName . '.create.padalinys');
    }
    
    /**
     * commonChecker
     * This function checks resource models by a common pattern. First, it checks for wildcard,
     * then if the user has permission to view own models, and finally if the user has permission
     * to view models of the same padalinys.
     * 
     * The implementation may be finicky.
     *
     * @param  User $user
     * @param  Model $model
     * @param  string $ability
     * @param  string $relationToDuties
     * @return bool
     */
    protected function commonChecker(User $user, Model $model, string $ability, string $relationToDuties, $hasManyPadalinys = true): bool
    {
        if ($this->authorizer->forUser($user)->check($this->pluralModelName . '.' . $ability . '.' . PermissionScopeEnum::ALL()->label)) {
            return true;
        }

        if ($this->authorizer->forUser($user)->check($this->pluralModelName . '.' . $ability . '.' . PermissionScopeEnum::OWN()->label)) {
            
            $permissableDuties = $this->authorizer->getPermissableDuties();

            $permissableModels = new Collection($permissableDuties->$relationToDuties);
            
            if ($permissableModels->contains($model)) {
                return true;
            };
        }

        $padalinysRelation = $hasManyPadalinys ? 'padaliniai' : 'padalinys';

        // If the user has permission to view padalinys and user belongs to same padalinys as the institution
        if ($this->authorizer->forUser($user)->check($this->pluralModelName . '.' . $ability . '.' . PermissionScopeEnum::PADALINYS()->label)) {
            
            $permissablePadaliniai = $user->padaliniai()
                ->whereIn('duties.id', $this->authorizer->getPermissableDuties()
                ->pluck('id'))
                ->get();

            $padaliniaiIntersect = $model->load($padalinysRelation)->$padalinysRelation->intersect($permissablePadaliniai);
            
            if ($padaliniaiIntersect->isNotEmpty()) {
                return true;
            };
        }

        return false;
    }
}