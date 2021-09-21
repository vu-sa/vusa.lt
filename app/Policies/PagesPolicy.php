<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagesPolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isCommunication();
    }

    public function handleEN(User $user) {
        return $user->isAdmin();
    }
}
