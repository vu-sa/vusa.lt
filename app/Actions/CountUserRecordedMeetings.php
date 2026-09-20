<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\Meeting;
use App\Models\User;
use App\Support\MorphMap;

/**
 * How many meetings a user has recorded (R-f, U13): the activity log's `created` rows they caused.
 *
 * `activitylog:clean` keeps 365 days, so a calendar year is always complete but "ever" is not; only
 * ask for "ever" when the answer is just "at least one".
 */
class CountUserRecordedMeetings
{
    public static function execute(User $user, ?int $year = null): int
    {
        return Activity::query()
            ->causedBy($user)
            ->where('subject_type', MorphMap::alias(Meeting::class))
            ->where('event', 'created')
            ->when($year !== null, fn ($query) => $query->whereYear('created_at', $year))
            ->count();
    }
}
