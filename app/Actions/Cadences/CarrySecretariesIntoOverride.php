<?php

namespace App\Actions\Cadences;

use App\Models\Cadence;
use App\Models\InstitutionSecretary;
use Illuminate\Support\Collection;

/**
 * Copies an institution's secretaries from the shared term an override replaces.
 *
 * A first override retires the whole global ladder for that institution, so without this
 * a roster staffed on the shared term would silently stop applying and could no longer be
 * seen or edited. The global rows are kept: deleting the override brings them back.
 */
class CarrySecretariesIntoOverride
{
    public static function execute(Cadence $override): void
    {
        if ($override->institution_id === null) {
            return;
        }

        /** @var Collection<int, InstitutionSecretary> $globalAssignments */
        $globalAssignments = InstitutionSecretary::query()
            ->where('institution_id', $override->institution_id)
            ->whereHas('cadence', fn ($query) => $query->globalLadder()
                ->whereDate('start_date', '<=', $override->end_date)
                ->whereDate('end_date', '>=', $override->start_date))
            ->with('cadence')
            ->get();

        $source = $globalAssignments
            ->pluck('cadence')
            ->unique('id')
            ->sortByDesc(fn (Cadence $cadence) => self::overlapDays($cadence, $override))
            ->first();

        if ($source === null) {
            return;
        }

        $alreadyNominated = $override->secretaryAssignments()->pluck('user_id');

        $globalAssignments
            ->where('cadence_id', $source->id)
            ->reject(fn (InstitutionSecretary $assignment) => $alreadyNominated->contains($assignment->user_id))
            ->each(fn (InstitutionSecretary $assignment) => InstitutionSecretary::create([
                'institution_id' => $override->institution_id,
                'cadence_id' => $override->id,
                'user_id' => $assignment->user_id,
            ]));
    }

    private static function overlapDays(Cadence $a, Cadence $b): int
    {
        $start = $a->start_date->max($b->start_date);
        $end = $a->end_date->min($b->end_date);

        return (int) $start->diffInDays($end, false);
    }
}
