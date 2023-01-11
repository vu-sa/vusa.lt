<?php

namespace App\Policies;

use App\Models\Doing;
use App\Models\Matter as Matter;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class DoingPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::DOING()->label);
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
