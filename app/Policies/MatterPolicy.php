<?php

namespace App\Policies;

use App\Models\Matter as Matter;
use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::MATTER()->label);
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
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Matter $matter)
    {
        if ($matter->users->contains($user)) {
            return true;
        }

        if ($user->padaliniai()->contains($matter->institution->padalinys)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if ($user->can('create institution content')) {
            return true;
        }
        
        if (!request()->has('matter_id')) {
            return false;
        }

        if (Matter::find(request()->matter_id)->users->contains($user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Matter $matter)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Matter $matter)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Matter $matter)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Matter $matter)
    {
        //
    }
}
