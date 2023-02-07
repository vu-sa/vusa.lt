<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\MainPage;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class MainPagePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::MAIN_PAGE()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, MainPage $mainPage, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $mainPage, CRUDEnum::READ()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, MainPage $mainPage, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $mainPage, CRUDEnum::UPDATE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, MainPage $mainPage, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $mainPage, CRUDEnum::DELETE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
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
