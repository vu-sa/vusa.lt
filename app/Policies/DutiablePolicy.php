<?php

namespace App\Policies;

use App\Models\Pivots\Dutiable;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class DutiablePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::DUTIABLE()->label);
    }

    

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Dutiable  $dutiable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Dutiable $dutiable)
    {
        //
    }



    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Dutiable  $dutiable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Dutiable $dutiable)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Dutiable  $dutiable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Dutiable $dutiable)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Dutiable  $dutiable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Dutiable $dutiable)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Dutiable  $dutiable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Dutiable $dutiable)
    {
        //
    }
}
