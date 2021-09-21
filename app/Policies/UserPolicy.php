<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function handleFiles(User $user) {
        return $user->isCommunication();
    }

    public function handleUsers(User $user) {
        return $user->isAdmin();
    }
}
