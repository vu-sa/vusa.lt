<?php

namespace App\Policies;

use App\Models\DutyInstitution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DutyInstitutionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('create unit duties') || $user->can('create institution content');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, DutyInstitution $dutyInstitution)
    {
        // If an user is part of the institution
        if ($dutyInstitution->users->contains($user)) {
            return true;
        }

        // If the user belongs to same padalinys as the institution
        if ($user->padaliniai()->contains($dutyInstitution->padalinys)) {
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
        return $user->can('create unit duties') || $user->can('create institution content');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, DutyInstitution $dutyInstitution)
    {
        if ($user->can('edit unit duties') || $user->can('edit institution content')) {
            return $user->padalinys()->id == $dutyInstitution->padalinys->id;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, DutyInstitution $dutyInstitution)
    {
        if ($user->can('delete unit duties')) {
            return $user->padalinys()->id == $dutyInstitution->padalinys->id;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, DutyInstitution $dutyInstitution)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, DutyInstitution $dutyInstitution)
    {
        //
    }
}