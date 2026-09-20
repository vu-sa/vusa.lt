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
        return $this->preferredEmail($user);
    }

    /**
     * The first current duty email ending in vusa.lt, else the personal email. Also the address a
     * mail signature shows, so a reply reaches the role rather than one person's inbox.
     */
    public function preferredEmail(User $user): string
    {
        foreach ($user->current_duties()->get() as $duty) {
            if (str_ends_with((string) $duty->email, 'vusa.lt')) {
                return $duty->email;
            }
        }

        return $user->email;
    }
}
