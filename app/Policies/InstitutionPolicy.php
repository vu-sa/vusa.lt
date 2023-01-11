<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::INSTITUTION()->label);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $this->forUser($user)->check($this->pluralModelName . '.read.padalinys');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Institution $institution)
    {
        if ($this->institutionCheck($user, $institution, 'read')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $this->forUser($user)->check($this->pluralModelName . '.create.padalinys');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Institution $institution)
    {
        if ($this->institutionCheck($user, $institution, 'update')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Institution $institution)
    {
        // Doesn't make sense to delete own institution
        if ($this->institutionCheck($user, $institution, 'delete')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Institution $institution)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Institution $institution)
    {
        //
    }

    protected function institutionCheck(User $user, Institution $institution, string $ability): bool
    {
        if ($this->forUser($user)->check($this->pluralModelName . '.' . $ability . '.' . 'own')) {
            
            $permissableDuties = $this->getPermissableDuties();
            
            foreach ($permissableDuties as $duty) {
                if ($duty->institution()->where('id', $institution->id)->exists()) {
                    return true;
                }
            }
        }

        // If the user has permission to view padalinys and user belongs to same padalinys as the institution
        if ($this->forUser($user)->check($this->pluralModelName . '.' . $ability . '.' . 'padalinys')) {
            
            $permissablePadaliniai = $user->padaliniai()
                ->whereIn('duties.id', $this->getPermissableDuties()
                ->pluck('id'))
                ->get();
            
            if ($permissablePadaliniai->contains($institution->padalinys)) {
                return true;
            };
        }

        return false;
    }
}
