<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Membership;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class MembershipPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::TYPE()->label);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Membership $membership): bool
    {
        if ($this->commonChecker($user, $membership, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Membership $membership): bool
    {
        if ($this->commonChecker($user, $membership, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }
}
