<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Get student representatives who are currently active for an institution.
 *
 * Finds users who have duties with the 'studentu-atstovai' type
 * for the given institution and are currently active based on dutiables dates.
 */
class GetInstitutionRepresentatives
{
    /**
     * Execute the action to get current representatives for an institution.
     *
     * @param  Institution  $institution  The institution to get representatives for
     * @param  Carbon|null  $date  The date to check (defaults to today)
     * @return Collection<int, User>
     */
    public static function execute(Institution $institution, ?Carbon $date = null): Collection
    {
        $checkDate = $date?->toDateString() ?? Carbon::today()->toDateString();

        // Get the student representative type
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first();

        if (! $studentRepType) {
            return new Collection;
        }

        // Get duty IDs for this institution with the student rep type
        $dutyIds = Duty::query()
            ->where('institution_id', $institution->id)
            ->whereHas('types', fn ($q) => $q->where('types.id', $studentRepType->id))
            ->pluck('id');

        if ($dutyIds->isEmpty()) {
            return new Collection;
        }

        // Get users who are currently active in these duties
        return User::query()
            ->whereHas('duties', function ($query) use ($dutyIds, $checkDate) {
                $query->whereIn('duties.id', $dutyIds)
                    ->where('dutiables.start_date', '<=', $checkDate)
                    ->where(function ($q) use ($checkDate) {
                        $q->whereNull('dutiables.end_date')
                            ->orWhere('dutiables.end_date', '>=', $checkDate);
                    });
            })
            ->get()
            ->unique('id');
    }
}
