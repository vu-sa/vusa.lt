<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionFollow;
use App\Models\InstitutionNotificationMute;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Service for managing institution subscription preferences.
 *
 * Handles follow/unfollow, mute/unmute, and reset operations
 * for institution notifications.
 */
class InstitutionSubscriptionService
{
    /**
     * Follow an institution.
     */
    public function follow(User $user, Institution $institution): InstitutionFollow
    {
        return InstitutionFollow::firstOrCreate([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ]);
    }

    /**
     * Unfollow an institution.
     * Also clears any mute status for the institution.
     */
    public function unfollow(User $user, Institution $institution): bool
    {
        // Also remove mute when unfollowing
        $this->unmute($user, $institution);

        return InstitutionFollow::where([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ])->delete() > 0;
    }

    /**
     * Mute notifications for an institution.
     */
    public function mute(User $user, Institution $institution): InstitutionNotificationMute
    {
        return InstitutionNotificationMute::firstOrCreate([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ], [
            'muted_at' => Carbon::now(),
        ]);
    }

    /**
     * Unmute notifications for an institution.
     */
    public function unmute(User $user, Institution $institution): bool
    {
        return InstitutionNotificationMute::where([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ])->delete() > 0;
    }

    /**
     * Reset all subscription preferences to defaults.
     *
     * @param  bool  $clearFollows  Whether to also clear followed institutions
     */
    public function resetToDefaults(User $user, bool $clearFollows = false): void
    {
        // Clear all mutes
        $user->mutedInstitutions()->detach();

        if ($clearFollows) {
            $user->followedInstitutions()->detach();
        }
    }

    /**
     * Get subscription status for an institution.
     *
     * @return array{is_followed: bool, is_muted: bool, is_duty_based: bool}
     */
    public function getStatus(User $user, Institution $institution): array
    {
        return [
            'is_followed' => $user->follows($institution),
            'is_muted' => $user->isInstitutionMuted($institution),
            'is_duty_based' => $user->hasInstitution($institution),
        ];
    }

    /**
     * Toggle follow state for an institution.
     *
     * @return bool New follow state (true if now following)
     */
    public function toggleFollow(User $user, Institution $institution): bool
    {
        if ($user->follows($institution)) {
            $this->unfollow($user, $institution);

            return false;
        }

        $this->follow($user, $institution);

        return true;
    }

    /**
     * Toggle mute state for an institution.
     *
     * @return bool New mute state (true if now muted)
     */
    public function toggleMute(User $user, Institution $institution): bool
    {
        if ($user->isInstitutionMuted($institution)) {
            $this->unmute($user, $institution);

            return false;
        }

        $this->mute($user, $institution);

        return true;
    }
}
