<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Pivots\Relationshipable;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class RelationshipablePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::RELATIONSHIPABLE()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Relationshipable  $relationshipable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Relationshipable $relationshipable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationshipable, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Relationshipable  $relationshipable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Relationshipable $relationshipable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationshipable, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Relationshipable  $relationshipable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Relationshipable $relationshipable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;
        
        if ($this->commonChecker($user, $relationshipable, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Relationshipable  $relationshipable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Relationshipable $relationshipable)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\Relationshipable  $relationshipable
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Relationshipable $relationshipable)
    {
        //
    }
}
