<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Goal;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class GoalPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::GOAL()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Goal $goal, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $goal, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Goal $goal, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $goal, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Goal $goal, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $goal, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Goal $goal)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Goal $goal)
    {
        //
    }
}
