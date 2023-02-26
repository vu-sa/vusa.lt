<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class DutiablePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::DUTIABLE()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Dutiable $dutiable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $dutiable, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Dutiable $dutiable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $dutiable, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Dutiable $dutiable, ModelAuthorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $dutiable, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Dutiable $dutiable)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Dutiable $dutiable)
    {
        //
    }
}
