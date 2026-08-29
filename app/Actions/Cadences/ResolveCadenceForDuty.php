<?php

namespace App\Actions\Cadences;

use App\Models\Cadence;
use App\Models\Duty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resolves which term a duty belongs to.
 *
 * An institution's own cadences win over the global ladder outright — a body that
 * defines its own boundaries never falls back per-year, or a missing 2027 row would
 * silently hand Parlamentas the July boundary it does not use.
 */
class ResolveCadenceForDuty
{
    public static function execute(Duty $duty, ?Carbon $reference = null): ?Cadence
    {
        return self::forDuties(collect([$duty]), $reference)[$duty->id] ?? null;
    }

    /**
     * Batch variant — one query for every institution in play. Mandatory for the
     * institution scope, which can span 30+ duties.
     *
     * @param  Collection<int, Duty>  $duties
     * @return array<string, Cadence|null> keyed by duty id
     */
    public static function forDuties(Collection $duties, ?Carbon $reference = null): array
    {
        $reference ??= Carbon::today();

        $institutionIds = $duties->pluck('institution_id')->filter()->unique()->values();

        $cadences = Cadence::query()
            ->where(function ($query) use ($institutionIds): void {
                $query->whereNull('institution_id');

                if ($institutionIds->isNotEmpty()) {
                    $query->orWhereIn('institution_id', $institutionIds->all());
                }
            })
            ->orderBy('start_date')
            ->get()
            ->groupBy(fn (Cadence $cadence) => $cadence->institution_id ?? '');

        $global = $cadences->get('', collect());

        $resolved = [];

        foreach ($duties as $duty) {
            $candidates = $duty->institution_id
                ? $cadences->get($duty->institution_id, collect())
                : collect();

            if ($candidates->isEmpty()) {
                $candidates = $global;
            }

            $resolved[$duty->id] = self::pick($candidates, $reference);
        }

        return $resolved;
    }

    /**
     * The term containing the reference date, else the next upcoming, else the latest past.
     *
     * @param  Collection<int, Cadence>  $candidates
     */
    private static function pick(Collection $candidates, Carbon $reference): ?Cadence
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->first(fn (Cadence $cadence) => $cadence->contains($reference))
            ?? $candidates->first(fn (Cadence $cadence) => $cadence->start_date->greaterThan($reference))
            ?? $candidates->last();
    }
}
