<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Institution;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

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
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Institution $institution)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Institution $institution)
    {
        //
    }
}
