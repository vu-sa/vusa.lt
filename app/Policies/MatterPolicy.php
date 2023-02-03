<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Matter as Matter;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\ModelAuthorizer as Authorizer;

class MatterPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::MATTER()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Matter $matter, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $matter, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Matter $matter, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $matter, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Matter $matter, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $matter, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Matter $matter)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Matter $matter)
    {
        //
    }
}
