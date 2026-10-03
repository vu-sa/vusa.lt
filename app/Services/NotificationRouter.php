<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationRouter
{
    /**
     * Immediate mail goes where the digest goes: the addresses chosen on Pranešimų nustatymai.
     *
     * @return array<int, string>
     */
    public function routeForMail(User $user, Notification $notification): array
    {
        return $user->notificationEmails();
    }

    /**
     * The first current duty address — a vusa.lt one first — else the personal email. Reps expect
     * mail at the role's inbox; it is also the address a signature shows, so a reply reaches the role.
     */
    public function preferredEmail(User $user): string
    {
        $dutyEmails = $user->current_duties()->pluck('duties.email')->filter()->values();

        return $dutyEmails->first(fn (string $email): bool => str_ends_with($email, 'vusa.lt'))
            ?? $dutyEmails->first()
            ?? $user->email;
    }
}
