<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function handle(User $user) {
        return $user->isCommunication();
    }

    public function handleCB(User $user) {
        return $user->isAdmin();
    }
    
    public function handlePadaliniai(User $user) {
        return $user->isPadaliniaiCommunication();
    } 
}
