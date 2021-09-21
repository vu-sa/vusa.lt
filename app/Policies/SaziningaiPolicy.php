<?php

namespace App\Policies;

use App\Models\Saziningai;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaziningaiPolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isAdmin() || $user->isSaziningai();
    }
}
