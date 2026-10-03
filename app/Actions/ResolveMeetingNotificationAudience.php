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
     * The audience by why each person is in it; someone who both oversees and follows counts
     * as an overseer, so each person gets the notice once.
     *
     * @return array{overseers: Collection<int, User>, followers: Collection<int, User>}
     */
    public static function split(Meeting $meeting): array
    {
        $overseers = GetMeetingOverseers::execute($meeting)->unique('id')->values();
        $overseerIds = $overseers->pluck('id');

        return [
            'overseers' => $overseers,
            'followers' => GetInstitutionFollowersToNotify::execute($meeting)
                ->reject(fn (User $user): bool => $overseerIds->contains($user->id))
                ->values(),
        ];
    }
}
