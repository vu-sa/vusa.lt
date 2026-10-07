<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

class GetInstitutionFollowersToNotify
{
    /**
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

        // Anyone may follow an active institution, but only hears about meetings they may read.
        return $followers->unique('id')
            ->filter(fn (User $follower): bool => $follower->can('viewSummary', $meeting))
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public static function getFollowersForInstitution(Institution $institution): Collection
    {
        return $institution->followers()->get();
    }
}
