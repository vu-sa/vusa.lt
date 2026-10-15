<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Who carries a task for an institution: its nominated secretaries, or — when
 * nobody is nominated for the relevant term — the members who were genuinely active
 * on the relevant date (O22).
 *
 * Nominating secretaries is what keeps a sitting of a 46-seat body from landing in
 * 46 inboxes, so this is a replacement, not a union. Notification audiences (meeting
 * reminders, meeting-created) merge the two instead; only task assignment narrows.
 */
class ResolveTaskAssignees
{
    /**
     * @return Collection<int, User>
     */
    public static function forMeeting(Meeting $meeting): Collection
    {
        $secretaries = GetInstitutionSecretaries::forMeeting($meeting);

        if ($secretaries->isNotEmpty()) {
            return $secretaries;
        }

        return collect($meeting->getRepresentativesActiveAt()->all());
    }

    /**
     * @return Collection<int, User>
     */
    public static function forInstitution(Institution $institution, ?Carbon $date = null): Collection
    {
        $secretaries = GetInstitutionSecretaries::execute($institution, $date);

        if ($secretaries->isNotEmpty()) {
            return $secretaries;
        }

        return collect(GetInstitutionRepresentatives::execute($institution, $date)->all());
    }
}
