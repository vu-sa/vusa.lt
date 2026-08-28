<?php

namespace App\Support\Dutiables;

use App\Models\Cadence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * How an assignment's period is measured against terms.
 *
 * An assignment belongs to *every* term its period touches — people stay on and get
 * re-elected, so a two- or three-term seat is ordinary. The single `primary` term exists
 * only so a chart can sort; it is never the answer to "did this person serve in 2024–2025".
 */
final class CadenceMatcher
{
    /**
     * An institution that defines its own cadences never falls back to the global ladder —
     * same rule as ResolveCadenceForDuty, or Parlamentas would be measured against a July
     * boundary it does not use.
     *
     * @param  Collection<int, Cadence>  $cadences
     * @return Collection<int, Cadence>
     */
    public static function applicable(Collection $cadences, ?string $institutionId): Collection
    {
        $scoped = $institutionId === null
            ? collect()
            : $cadences->where('institution_id', $institutionId);

        return $scoped->isNotEmpty()
            ? $scoped->values()
            : $cadences->whereNull('institution_id')->values();
    }

    /**
     * Every term the period touches. An open end runs forever, so it reaches every later
     * term too — a sitting member must show up under the current one, not only the one
     * they started in.
     *
     * @param  Collection<int, Cadence>  $pool
     * @return Collection<int, Cadence>
     */
    public static function overlapping(Collection $pool, Carbon $start, ?Carbon $end): Collection
    {
        return $pool
            ->filter(fn (Cadence $cadence) => $cadence->end_date->greaterThanOrEqualTo($start)
                && ($end === null || $cadence->start_date->lessThanOrEqualTo($end)))
            ->sortBy(fn (Cadence $cadence) => $cadence->start_date->getTimestamp())
            ->values();
    }

    /**
     * The single term a row reads as "belonging to". Sorting only — membership is the list.
     *
     * Containment of the start date is what this used to be, and it put every June-starting
     * seat in the term that was ending rather than the one it was elected into. A June start
     * running through to the next summer is no contest — a fortnight in one term against a
     * year in the next — so overlap settles it, with ties going to the earlier term.
     *
     * An open end is the exception: it fills every term from its start onwards, so there is
     * no "most" to measure and the only honest answer is the term it began in. That also
     * keeps a long-serving member sorted beside the people they started with.
     *
     * @param  Collection<int, Cadence>  $pool
     */
    public static function primary(Collection $pool, Carbon $start, ?Carbon $end): ?Cadence
    {
        if ($pool->isEmpty()) {
            return null;
        }

        $overlapping = self::overlapping($pool, $start, $end);

        if ($overlapping->isNotEmpty()) {
            return $end === null
                ? $overlapping->first()
                : $overlapping
                    ->sortBy(fn (Cadence $cadence) => [
                        -self::overlapDays($cadence, $start, $end),
                        $cadence->start_date->getTimestamp(),
                    ])
                    ->first();
        }

        // Nothing overlaps: measure to the whole window rather than to its start, or a date
        // sitting deep inside no term still snaps backwards to the term before it.
        return $pool
            ->sortBy(fn (Cadence $cadence) => self::distanceToWindow($cadence, $start))
            ->first();
    }

    /**
     * How much of the period falls inside the term. Only ever asked about a closed period —
     * an open end has no length to compare, and primary() answers it another way.
     */
    private static function overlapDays(Cadence $cadence, Carbon $start, Carbon $end): int
    {
        $from = $start->greaterThan($cadence->start_date) ? $start : $cadence->start_date;
        $to = $end->greaterThan($cadence->end_date) ? $cadence->end_date : $end;

        return max(0, (int) $from->diffInDays($to, absolute: false)) + 1;
    }

    private static function distanceToWindow(Cadence $cadence, Carbon $date): int
    {
        if ($date->lessThan($cadence->start_date)) {
            return (int) $date->diffInDays($cadence->start_date, absolute: true);
        }

        if ($date->greaterThan($cadence->end_date)) {
            return (int) $date->diffInDays($cadence->end_date, absolute: true);
        }

        return 0;
    }
}
