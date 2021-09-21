<?php

namespace App\Policies;

use App\Models\Navigation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NavigationPolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isAdmin();
    }

}
