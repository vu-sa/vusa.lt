<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationRouter
{
    /**
     * Determine the email address for a notification.
     * If the user has a current duty with a @vusa.lt email, route there.
     * Otherwise, fall back to the user's personal email.
     */
    public function routeForMail(User $user, Notification $notification): array|string
    {
        if ($user->current_duties()->count() > 0) {
            foreach ($user->current_duties()->get() as $duty) {
                if (str_ends_with($duty->email, 'vusa.lt')) {
                    return $duty->email;
                }
            }
        }

        return $user->email;
    }
}
