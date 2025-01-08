<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class UserPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::USER()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        if ($this->commonChecker($user, $model, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function merge(User $user)
    {
        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.update.*');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */

    // TODO:: fix this policy to use for each
    public function update(User $user, User $model)
    {
        if ($this->commonChecker($user, $model, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        /* if ($this->commonChecker($user, $model, CRUDEnum::DELETE()->label, $this->pluralModelName)) { */
        /*    return true; */
        /* } */
        /**/
        /* return false; */

        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.delete.*');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        return false;
    }

    // TODO: wild policies
    public function storeFromMicrosoft(User $user)
    {
        return true;
    }
}
