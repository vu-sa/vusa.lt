<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Meeting;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::MEETING()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Meeting $meeting, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $meeting, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Meeting $meeting, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $meeting, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Meeting $meeting, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $meeting, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Meeting $meeting)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Meeting $meeting)
    {
        //
    }
}