<?php

namespace App\Policies;

use App\Models\MainPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MainPagePolicy
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
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, MainPage $mainPage)
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
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, MainPage $mainPage)
    {
        if ($user->can('edit unit content')) {
            return $user->padalinys()->id == $mainPage->padalinys->id;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, MainPage $mainPage)
    {
        if ($user->can('delete unit content')) {
            return $user->padalinys()->id == $mainPage->padalinys->id;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, MainPage $mainPage)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, MainPage $mainPage)
    {
        //
    }
}
