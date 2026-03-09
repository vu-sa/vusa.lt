<?php

namespace App\Actions;

use App\Models\User;

class DeleteUserPassword
{
    /**
     * Remove a user's password, requiring them to use OAuth for login.
     *
     * @param  User  $user  The user to remove the password from
     */
    public static function execute(User $user): void
    {
        $user->password = null;
        $user->save();
    }
}
