<?php

namespace App\Actions\Dutiables;

use App\Models\Cadence;
use App\Models\Pivots\Dutiable;
use App\Support\Dutiables\CadenceMatcher;
use App\Support\Dutiables\DutiableDiagnostic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Finds the drift the timeline exists to fix.
 *
 * Runs entirely over rows that are already loaded — the only query is the ex-officio
 * orphan sweep, which needs a join the payload does not carry. The same checks are
 * mirrored client-side over *staged* state so the panel updates during a drag; this
 * version is the authoritative one, and is what the preview's before/after delta uses.
 */
class AnalyzeDutiableTimeline
{
    /** Beyond this an off-boundary date reads as deliberate rather than as drift. */
    public const int OFF_CADENCE_MAX_DAYS = 45;

    /**
     * @param  Collection<int, Dutiable>  $rows
     * @param  Collection<int, Cadence>  $cadences
     * @param  array<string, array{start_date: string, end_date: string|null}>  $overrides
     *                                                                                      projected state, so the preview can diff "before" against "after"
     * @return list<array<string, mixed>>
     */
    public static function execute(Collection $rows, Collection $cadences, array $overrides = []): array
    {
        $periods = self::periods($rows, $overrides);

        $findings = [
            ...self::inverted($rows, $periods),
            ...self::overlapsAndBoundaries($rows, $periods),
            ...self::openEndedStale($rows, $periods, $cadences),
            ...self::exOfficioDrift($rows, $periods),
            ...self::offCadence($rows, $periods, $cadences),
            ...self::spansCadences($rows, $periods, $cadences),
            ...self::understaffed($rows, $periods),
            ...self::orphanDerivedSuspects($rows),
        ];

        return array_map(fn (DutiableDiagnostic $diagnostic) => $diagnostic->toArray(), $findings);
    }

    /**
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start_date: string, end_date: string|null}>  $overrides
     * @return array<string, array{start: string, end: string|null}>
     */
    private static function periods(Collection $rows, array $overrides): array
    {
        $periods = [];

        foreach ($rows as $row) {
            $override = $overrides[$row->id] ?? null;

            $periods[$row->id] = [
                'start' => $override['start_date'] ?? $row->start_date->toDateString(),
                'end' => $override !== null ? $override['end_date'] : $row->end_date?->toDateString(),
            ];
        }

        return $periods;
    }

    /**
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @return list<DutiableDiagnostic>
     */
    private static function inverted(Collection $rows, array $periods): array
    {
        $findings = [];

        foreach ($rows as $row) {
            $period = $periods[$row->id];

            if ($period['end'] !== null && $period['end'] < $period['start']) {
                $findings[] = new DutiableDiagnostic(
                    'inverted', DutiableDiagnostic::SEVERITY_ERROR, [$row->id], $row->duty_id, $period,
                );
            }
        }

        return $findings;
    }

    /**
     * Two checks over the same walk, because both compare consecutive stints of one
     * holder on one duty.
     *
     * The grouping key includes `tenant_id`: an owning-tenant row and a cross-tenant
     * representative row for the same person are legitimately concurrent, and folding
     * them would be wrong (CollapseOverlappingDutiables groups the same way).
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @return list<DutiableDiagnostic>
     */
    private static function overlapsAndBoundaries(Collection $rows, array $periods): array
    {
        $findings = [];

        $groups = $rows->groupBy(fn (Dutiable $row) => implode('|', [$row->duty_id, $row->dutiable_id, $row->tenant_id]));

        foreach ($groups as $group) {
            $sorted = $group->sortBy(fn (Dutiable $row) => $periods[$row->id]['start'])->values();

            for ($i = 1; $i < $sorted->count(); $i++) {
                $earlier = $sorted[$i - 1];
                $later = $sorted[$i];

                $earlierEnd = $periods[$earlier->id]['end'];
                $laterStart = $periods[$later->id]['start'];

                if ($earlierEnd === null || $earlierEnd > $laterStart) {
                    $findings[] = new DutiableDiagnostic(
                        'overlap',
                        DutiableDiagnostic::SEVERITY_ERROR,
                        [$earlier->id, $later->id],
                        $earlier->duty_id,
                        ['suggested_end' => Carbon::parse($laterStart)->subDay()->toDateString()],
                    );

                    continue;
                }

                // One term ending the day the next begins is the 592-row habit the editor
                // exists to unpick — legal in the database, wrong in every reading of it.
                if ($earlierEnd === $laterStart) {
                    $findings[] = new DutiableDiagnostic(
                        'boundary_shared',
                        DutiableDiagnostic::SEVERITY_WARNING,
                        [$earlier->id, $later->id],
                        $earlier->duty_id,
                        ['suggested_end' => Carbon::parse($laterStart)->subDay()->toDateString()],
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * A row with no end whose own term has already finished: it was never closed, and
     * every quota check still counts the seat as taken.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @param  Collection<int, Cadence>  $cadences
     * @return list<DutiableDiagnostic>
     */
    private static function openEndedStale(Collection $rows, array $periods, Collection $cadences): array
    {
        $today = now()->toDateString();
        $findings = [];

        foreach ($rows as $row) {
            if ($periods[$row->id]['end'] !== null || $row->via_dutiable_id !== null) {
                continue;
            }

            $cadence = self::cadenceContaining($cadences, $row, $periods[$row->id]['start']);

            if ($cadence === null || $cadence->end_date->toDateString() >= $today) {
                continue;
            }

            $findings[] = new DutiableDiagnostic(
                'open_ended_stale',
                DutiableDiagnostic::SEVERITY_WARNING,
                [$row->id],
                $row->duty_id,
                ['cadence_id' => $cadence->id, 'suggested_end' => $cadence->end_date->toDateString()],
            );
        }

        return $findings;
    }

    /**
     * A derived row whose dates no longer match its source — the queued sync either never
     * ran or was overtaken by a direct edit.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @return list<DutiableDiagnostic>
     */
    private static function exOfficioDrift(Collection $rows, array $periods): array
    {
        $findings = [];

        foreach ($rows as $row) {
            if ($row->via_dutiable_id === null || ! isset($periods[$row->via_dutiable_id])) {
                continue;
            }

            if ($periods[$row->id] !== $periods[$row->via_dutiable_id]) {
                $findings[] = new DutiableDiagnostic(
                    'ex_officio_drift',
                    DutiableDiagnostic::SEVERITY_WARNING,
                    [$row->id],
                    $row->duty_id,
                    ['source_id' => $row->via_dutiable_id, 'source' => $periods[$row->via_dutiable_id]],
                );
            }
        }

        return $findings;
    }

    /**
     * Each edge is measured against **its own** term, not against the start's.
     *
     * A row running 2024-07-01 → 2026-06-30 covers two cadences; comparing its end to the
     * 2024–2025 boundary reported drift of a full year, which the ≤45-day clamp then
     * silently swallowed. Resolving per edge is what makes such a row fixable.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @param  Collection<int, Cadence>  $cadences
     * @return list<DutiableDiagnostic>
     */
    private static function offCadence(Collection $rows, array $periods, Collection $cadences): array
    {
        $findings = [];

        foreach ($rows as $row) {
            $period = $periods[$row->id];
            $drift = [];
            $cadenceIds = [];

            foreach (['start', 'end'] as $edge) {
                $value = $period[$edge];

                if ($value === null) {
                    continue;
                }

                $cadence = self::cadenceContaining($cadences, $row, $value)
                    ?? self::nearestCadence($cadences, $row, $value);

                if ($cadence === null) {
                    continue;
                }

                $boundary = $edge === 'start' ? $cadence->start_date : $cadence->end_date;
                $days = (int) Carbon::parse($value)->diffInDays($boundary, absolute: true);

                if ($days > 0 && $days <= self::OFF_CADENCE_MAX_DAYS) {
                    $drift[$edge] = $days;
                    $cadenceIds[$edge] = $cadence->id;
                }
            }

            if ($drift !== []) {
                $findings[] = new DutiableDiagnostic(
                    'off_cadence',
                    DutiableDiagnostic::SEVERITY_INFO,
                    [$row->id],
                    $row->duty_id,
                    [
                        // The start's term stays the headline id so a single-cadence fix
                        // keeps working; `cadence_ids` is what a two-term row aligns by.
                        'cadence_id' => $cadenceIds['start'] ?? $cadenceIds['end'] ?? null,
                        'cadence_ids' => $cadenceIds,
                        'drift_days' => $drift,
                    ],
                );
            }
        }

        return $findings;
    }

    /**
     * A term covering more than one cadence. Reported, never auto-checked: a two-term
     * appointment is legitimate, so this is a question for the admin rather than a defect.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @param  Collection<int, Cadence>  $cadences
     * @return list<DutiableDiagnostic>
     */
    private static function spansCadences(Collection $rows, array $periods, Collection $cadences): array
    {
        $findings = [];

        foreach ($rows as $row) {
            $period = $periods[$row->id];

            if ($period['end'] === null || $row->via_dutiable_id !== null) {
                continue;
            }

            $start = Carbon::parse($period['start']);
            $end = Carbon::parse($period['end']);

            $covered = CadenceMatcher::overlapping(self::applicable($cadences, $row), $start, $end);

            if ($covered->count() < 2) {
                continue;
            }

            $findings[] = new DutiableDiagnostic(
                'spans_cadences',
                DutiableDiagnostic::SEVERITY_INFO,
                [$row->id],
                $row->duty_id,
                [
                    'count' => $covered->count(),
                    'cadence_ids' => $covered->pluck('id')->all(),
                    'suggested_start' => $covered->first()->start_date->toDateString(),
                    'suggested_end' => $covered->last()->end_date->toDateString(),
                ],
            );
        }

        return $findings;
    }

    /**
     * Seats standing empty today. Informational only — a duty is often deliberately
     * under-filled between terms.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @param  array<string, array{start: string, end: string|null}>  $periods
     * @return list<DutiableDiagnostic>
     */
    private static function understaffed(Collection $rows, array $periods): array
    {
        $today = now()->toDateString();
        $findings = [];

        foreach ($rows->groupBy('duty_id') as $dutyId => $group) {
            $places = $group->first()->duty?->places_to_occupy;

            if ($places === null || $places < 1) {
                continue;
            }

            $active = $group->filter(function (Dutiable $row) use ($periods, $today) {
                $period = $periods[$row->id];

                return $period['start'] <= $today && ($period['end'] === null || $period['end'] >= $today);
            })->count();

            if ($active < $places) {
                $findings[] = new DutiableDiagnostic(
                    'understaffed',
                    DutiableDiagnostic::SEVERITY_INFO,
                    [],
                    (string) $dutyId,
                    ['active' => $active, 'places_to_occupy' => $places],
                );
            }
        }

        return $findings;
    }

    /**
     * Reported, never fixed — see {@see FindSuspectExOfficioDutiables} for why guessing
     * here would take real access away from someone.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @return list<DutiableDiagnostic>
     */
    private static function orphanDerivedSuspects(Collection $rows): array
    {
        $dutyIds = $rows->pluck('duty_id')->unique()->values()->all();
        $suspectIds = array_intersect(
            FindSuspectExOfficioDutiables::idsForDuties($dutyIds),
            $rows->pluck('id')->all(),
        );

        return array_map(
            fn (string $rowId) => new DutiableDiagnostic(
                'orphan_derived_suspect',
                DutiableDiagnostic::SEVERITY_INFO,
                [$rowId],
                $rows->firstWhere('id', $rowId)?->duty_id,
            ),
            array_values($suspectIds),
        );
    }

    /**
     * An institution that defines its own cadences never falls back to the global ladder —
     * same rule as ResolveCadenceForDuty, or Parlamentas would be measured against a July
     * boundary it does not use.
     *
     * @param  Collection<int, Cadence>  $cadences
     */
    private static function applicable(Collection $cadences, Dutiable $row): Collection
    {
        return CadenceMatcher::applicable($cadences, $row->duty?->institution_id);
    }

    /**
     * @param  Collection<int, Cadence>  $cadences
     */
    private static function cadenceContaining(Collection $cadences, Dutiable $row, string $date): ?Cadence
    {
        return self::applicable($cadences, $row)
            ->first(fn (Cadence $cadence) => $cadence->contains(Carbon::parse($date)));
    }

    /**
     * @param  Collection<int, Cadence>  $cadences
     */
    private static function nearestCadence(Collection $cadences, Dutiable $row, string $date): ?Cadence
    {
        $reference = Carbon::parse($date);

        return self::applicable($cadences, $row)
            ->sortBy(fn (Cadence $cadence) => $cadence->start_date->diffInDays($reference, absolute: true))
            ->first();
    }
}
