<?php

namespace App\Policies;

use App\Models\Padalinys;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PadaliniaiPolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isAdmin();
    }

    public function handleEnConfiguration(User $user) {
        return $user->isPadaliniaiCommunication();
    }
}
