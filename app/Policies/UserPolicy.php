<?php

namespace App\Policies;

use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy extends ModelPolicy
{

    use HandlesAuthorization;

    

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::USER()->label);
    }

    

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        
    }


    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */

    // TODO:: fix this policy to use for each
    public function update(User $user, User $model)
    {       
        if ($user->can('edit unit users')) {
            return $model->padaliniai()->contains($user->padaliniai()->first()?->id) || (is_null($model->padaliniai()->first()));
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        if ($user->can('delete unit users')) {
            return $model->padaliniai()->contains($user->padaliniai()->first()->id) || (is_null($model->padaliniai()->first()));
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, User $model)
    {

    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        
    }

    public function storeFromMicrosoft(User $user)
    {
        return true;
    }

    public function detachFromDuty(User $user, User $model)
    {
        if ($user->can('edit unit users')) {
            return $model->padaliniai()->contains($user->padaliniai()->first()->id) || (is_null($model->padaliniai()->first()));
        }
    }
}
