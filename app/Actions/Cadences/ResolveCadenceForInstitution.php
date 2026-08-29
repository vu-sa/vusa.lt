<?php

namespace App\Actions\Cadences;

use App\Models\Cadence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resolves which term an institution was in on a given date.
 *
 * An institution's own cadences win over the global ladder outright — the same rule
 * {@see ResolveCadenceForDuty::pick()} applies, and the two must move together.
 *
 * Unlike that action this resolves *strictly by containment*: a date outside every
 * term returns null rather than falling forward to the next one. Anything hanging off
 * a term (administrators, and whatever follows) must not silently apply to a meeting
 * held years before it existed.
 */
class ResolveCadenceForInstitution
{
    public static function execute(string $institutionId, ?Carbon $date = null): ?Cadence
    {
        return self::forInstitutions(collect([$institutionId]), $date)[$institutionId] ?? null;
    }

    /**
     * Batch variant — one query for every institution in play.
     *
     * @param  Collection<int, string>  $institutionIds
     * @return array<string, Cadence|null> keyed by institution id
     */
    public static function forInstitutions(Collection $institutionIds, ?Carbon $date = null): array
    {
        $date ??= Carbon::today();

        $ids = $institutionIds->unique()->values();

        $cadences = Cadence::query()
            ->where(function ($query) use ($ids): void {
                $query->whereNull('institution_id');

                if ($ids->isNotEmpty()) {
                    $query->orWhereIn('institution_id', $ids->all());
                }
            })
            ->orderBy('start_date')
            ->get()
            ->groupBy(fn (Cadence $cadence) => $cadence->institution_id ?? '');

        $global = $cadences->get('', collect());

        $resolved = [];

        foreach ($ids as $institutionId) {
            $candidates = $cadences->get($institutionId, collect());

            if ($candidates->isEmpty()) {
                $candidates = $global;
            }

            $resolved[$institutionId] = $candidates
                ->first(fn (Cadence $cadence) => $cadence->contains($date));
        }

        return $resolved;
    }
}
