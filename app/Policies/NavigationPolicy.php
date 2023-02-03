<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Navigation;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class NavigationPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::NAVIGATION()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Navigation $navigation, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $navigation, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Navigation $navigation, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $navigation, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Navigation $navigation, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $navigation, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Navigation $navigation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Navigation $navigation)
    {
        //
    }
}
