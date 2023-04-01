<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Initiative;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;

class InitiativePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::INITIATIVE()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Initiative $initiative, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $initiative, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Initiative $initiative, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $initiative, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Initiative $initiative, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $initiative, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Initiative $initiative): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Initiative $initiative): bool
    {
        return false;
    }
}
