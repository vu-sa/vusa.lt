<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Get all followers who should be notified about institution-related events.
 *
 * Collects users who have explicitly followed any of the meeting's institutions,
 * excluding users who have muted notifications for those institutions.
 */
class GetInstitutionFollowersToNotify
{
    /**
     * Execute the action to get unique followers to notify for a meeting.
     *
     * @return Collection<int, User>
     */
    public static function execute(Meeting $meeting): Collection
    {
        $meeting->loadMissing('institutions');

        $followers = collect();

        foreach ($meeting->institutions as $institution) {
            $institutionFollowers = self::getFollowersForInstitution($institution);
            $followers = $followers->merge($institutionFollowers);
        }

        // Return unique users by ID
        return $followers->unique('id')->values();
    }

    /**
     * Get followers for a single institution who haven't muted it.
     *
     * @return Collection<int, User>
     */
    public static function getFollowersForInstitution(Institution $institution): Collection
    {
        return $institution->followers()
            ->whereDoesntHave('mutedInstitutions', function ($query) use ($institution) {
                $query->where('institution_id', $institution->id);
            })
            ->get();
    }
}
