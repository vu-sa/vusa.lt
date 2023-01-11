<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Duty;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\ModelAuthorizer as Authorizer;

class DutyPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::DUTY()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Duty $duty, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $duty, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Duty $duty, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $duty, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Duty $duty, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $duty, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Duty $duty)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Duty $duty)
    {
        //
    }
}
