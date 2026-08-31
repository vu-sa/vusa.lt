<?php

namespace App\Actions;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Everyone who should hear about a meeting: the people who oversee it by position
 * ({@see GetMeetingOverseers}) plus the people who asked to watch its institutions
 * ({@see GetInstitutionFollowersToNotify}, which already drops the ones who muted it).
 *
 * A union, unlike task assignment: {@see ResolveTaskAssignees} narrows to the nominated
 * administrators precisely so a 46-seat body's sitting lands in one inbox rather than 46.
 * Being told a meeting exists is not the same as being asked to do something about it.
 */
class ResolveMeetingNotificationAudience
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Meeting $meeting): Collection
    {
        return GetMeetingOverseers::execute($meeting)
            ->merge(GetInstitutionFollowersToNotify::execute($meeting))
            ->unique('id')
            ->values();
    }
}
