<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\ModelAuthorizer as Authorizer;

class InstitutionPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::INSTITUTION()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Institution $institution, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $institution, 'read', 'institution', false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Institution $institution, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $institution, 'update', 'institution', false)) {
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
    public function delete(User $user, Institution $institution, Authorizer $authorizer)
    {
        // Doesn't make sense to delete own institution
        if ($this->commonChecker($user, $institution, 'delete', 'institution', false)) {
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
}
