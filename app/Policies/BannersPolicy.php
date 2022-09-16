<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BannersPolicy
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
        return $user->can('create unit content');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Banner $banner)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create unit content');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Banner $banner)
    {
        if ($user->can('edit unit content')) {
            return $user->padalinys()->id == $banner->padalinys->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Banner $banner)
    {
        if ($user->can('delete unit content')) {
            return $user->padalinys()->id == $banner->padalinys->id;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Banner $banner)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Banner $banner)
    {
        //
    }
}
