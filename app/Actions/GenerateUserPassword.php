<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

class GenerateUserPassword
{
    /**
     * Generate a random password for a user.
     *
     * @param  User  $user  The user to generate a password for
     * @param  int  $length  The length of the password (default: 10)
     * @return string The generated plain-text password (one-time visible)
     */
    public static function execute(User $user, int $length = 10): string
    {
        $password = Str::random($length);

        $user->password = bcrypt($password);
        $user->save();

        return $password;
    }
}
