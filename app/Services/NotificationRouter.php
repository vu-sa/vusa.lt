<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Contracts\SendsMailOnStaging;
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
        if (config('app.env') === 'staging') {
            $address = $this->stagingMailAddress($notification);

            return $address === null ? [] : [$address];
        }

        return $user->notificationEmails();
    }

    /**
     * Staging mails only opted-in notifications, and only to whoever caused them. Duty inboxes are
     * real addresses that the staging scrub leaves untouched, so they are never a staging target.
     */
    public function stagingMailAddress(Notification $notification): ?string
    {
        $email = $notification instanceof SendsMailOnStaging ? $notification->stagingMailRecipient()?->email : null;

        return is_string($email) && $email !== '' && ! str_ends_with($email, '@staging.invalid') ? $email : null;
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
