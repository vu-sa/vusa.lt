<?php

namespace App\Policies;

use App\Models\MainPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MainPagePolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isCommunication();
    }

    public function handleMain(User $user) {
        return $user->isAdmin();
    }
}
