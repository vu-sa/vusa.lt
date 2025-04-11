<?php

namespace App\Policies;

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
     * Determine whether the user can merge models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function merge(User $user)
    {
        return $this->authorizer->forUser($user)->check($this->pluralModelName.'.update.*');
    }
}
