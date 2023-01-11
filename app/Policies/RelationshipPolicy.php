<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Relationship;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class RelationshipPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::RELATIONSHIP()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Relationship $relationship, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationship, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Relationship $relationship, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationship, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Relationship $relationship, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationship, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Relationship $relationship)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Relationship $relationship)
    {
        //
    }
}
