<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingRepresentativeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everyone holding a duty in the institution on a given date — every duty, not only
 * the ones typed `studentu-atstovai`.
 *
 * This is the audience question ("who is in this body right now"), distinct from
 * {@see GetInstitutionRepresentatives} / {@see MeetingRepresentativeResolver},
 * which answer the narrower "who are its student representatives" and drive task
 * assignment. Notification audiences used to reach for `Institution::users()` or
 * `$duty->users`, which are all-time and pulled in holders who had left years earlier.
 */
class GetInstitutionMembers
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Institution $institution, ?Carbon $date = null): Collection
    {
        return self::forInstitutionIds(collect([$institution->id]), $date);
    }

    /**
     * Members of every institution the meeting belongs to, on the meeting's own date.
     *
     * @return Collection<int, User>
     */
    public static function forMeeting(Meeting $meeting): Collection
    {
        $meeting->loadMissing('institutions');

        return self::forInstitutionIds(
            collect($meeting->institutions->pluck('id')->all()),
            Carbon::instance($meeting->start_time),
        );
    }

    /**
     * @param  Collection<int, string>  $institutionIds
     * @return Collection<int, User>
     */
    private static function forInstitutionIds(Collection $institutionIds, ?Carbon $date = null): Collection
    {
        if ($institutionIds->isEmpty()) {
            return collect();
        }

        $checkDate = ($date ?? Carbon::today())->toDateString();

        /** @var Collection<int, User> $users */
        $users = User::query()
            ->whereHas('duties', function (Builder $query) use ($institutionIds, $checkDate): void {
                $query->whereIn('duties.institution_id', $institutionIds->all())
                    ->whereDate('dutiables.start_date', '<=', $checkDate)
                    ->where(fn (Builder $q) => $q
                        ->whereNull('dutiables.end_date')
                        ->orWhereDate('dutiables.end_date', '>=', $checkDate));
            })
            ->get();

        return $users->unique('id')->values();
    }
}
