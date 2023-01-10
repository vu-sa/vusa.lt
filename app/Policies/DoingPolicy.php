<?php

namespace App\Policies;

use App\Models\Doing;
use App\Models\Matter as Matter;
use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class DoingPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::DOING()->label);
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
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Doing $doing)
    {
        return $doing->users->contains($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if (!request()->has('matter_id')) {
            return false;
        }

        return Matter::find(request()->matter_id)->users->contains($user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Doing $doing)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Doing $doing)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Doing $doing)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Doing $doing)
    {
        //
    }
}
