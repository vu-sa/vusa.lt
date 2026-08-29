<?php

namespace App\Actions;

use App\Actions\Cadences\ResolveCadenceForInstitution;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The people nominated to look after an institution on a given date.
 *
 * Nominations hang off a term, so a date outside every cadence resolves to nobody —
 * which is what makes historical meetings fall back to the members who were actually
 * active then, rather than to today's roster.
 */
class GetInstitutionAdministrators
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Institution $institution, ?Carbon $date = null): Collection
    {
        $cadence = ResolveCadenceForInstitution::execute($institution->id, $date);

        if ($cadence === null) {
            return collect();
        }

        /** @var Collection<int, User> $administrators */
        $administrators = $institution->administrators()
            ->wherePivot('cadence_id', $cadence->id)
            ->get();

        return $administrators;
    }

    /**
     * Administrators of every institution the meeting belongs to, resolved at the
     * meeting's own date.
     *
     * @return Collection<int, User>
     */
    public static function forMeeting(Meeting $meeting): Collection
    {
        $meeting->loadMissing('institutions');

        $date = Carbon::instance($meeting->start_time);

        return $meeting->institutions
            ->flatMap(fn (Institution $institution) => self::execute($institution, $date))
            ->unique('id')
            ->values();
    }
}
