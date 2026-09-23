<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\Meeting;
use App\Models\User;
use App\Support\MorphMap;

/**
 * How many meetings a user has recorded (U13): the activity log's `created` rows they caused.
 *
 * `activitylog:clean` keeps 365 days, so the count is only reliable as "at least one".
 */
class CountUserRecordedMeetings
{
    public static function execute(User $user): int
    {
        return Activity::query()
            ->causedBy($user)
            ->where('subject_type', MorphMap::alias(Meeting::class))
            ->where('event', 'created')
            ->count();
    }
}
