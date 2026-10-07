<?php

namespace App\Actions;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Notification audiences union overseers and followers; task assignment narrows to administrators.
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
