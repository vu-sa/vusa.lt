<?php

namespace App\Actions\Dutiables;

use App\Models\Cadence;
use App\Models\Pivots\Dutiable;
use App\Support\Dutiables\DutiableTimelineChange;
use App\Support\Dutiables\DutiableTimelinePlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Folds a timeline operation list into a concrete per-row move.
 *
 * The client sends operations, never dates it expects to be trusted: the drag emits
 * `set_dates`, the dock's buttons and the suggestion list emit the rest, and both the
 * dry-run preview and the write re-derive the outcome here. Operations apply in order
 * over a running projection, so "close the open ends, then align" reads as it runs.
 */
class PlanDutiableTimelineChanges
{
    /** Beyond this an "off cadence" row is deliberate, not drift, and bulk align leaves it alone. */
    public const int DEFAULT_ALIGN_THRESHOLD_DAYS = 45;

    /**
     * @param  list<array<string, mixed>>  $operations
     */
    public static function execute(array $operations): DutiableTimelinePlan
    {
        $rowIds = self::targetedRowIds($operations);
        $rows = self::loadRows($rowIds);
        $cadences = self::cadencesFor($rows);

        /** @var array<string, array{start_date: string, end_date: string|null}> $state */
        $state = [];
        /** @var array<string, list<string>> $reasons */
        $reasons = [];

        foreach ($rows as $id => $row) {
            $state[$id] = [
                'start_date' => $row->start_date->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
            ];
            $reasons[$id] = [];
        }

        $original = $state;

        foreach ($operations as $operation) {
            foreach ($operation['row_ids'] as $rowId) {
                if (! isset($state[$rowId])) {
                    continue;
                }

                $next = self::apply($operation, $state[$rowId], self::applicable($cadences, $rows->get($rowId)));

                if ($next !== $state[$rowId]) {
                    $state[$rowId] = $next;
                    $reasons[$rowId][] = $operation['type'];
                }
            }
        }

        return self::buildPlan($rows, $original, $state, $reasons, $cadences);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array{start_date: string, end_date: string|null}  $current
     * @param  Collection<int, Cadence>  $cadences
     * @return array{start_date: string, end_date: string|null}
     */
    private static function apply(array $operation, array $current, Collection $cadences): array
    {
        return match ($operation['type']) {
            'set_dates' => self::setDates($operation, $current),
            'align_to_cadence' => self::alignToCadence($operation, $current, $cadences),
            'close_open_ended' => self::closeOpenEnded($operation, $current),
            default => $current,
        };
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array{start_date: string, end_date: string|null}  $current
     * @return array{start_date: string, end_date: string|null}
     */
    private static function setDates(array $operation, array $current): array
    {
        // `end_date` present-but-null means "make this open-ended"; absent means "leave it".
        if (array_key_exists('start_date', $operation) && $operation['start_date'] !== null) {
            $current['start_date'] = $operation['start_date'];
        }

        if (array_key_exists('end_date', $operation)) {
            $current['end_date'] = $operation['end_date'];
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array{start_date: string, end_date: string|null}  $current
     * @param  Collection<int, Cadence>  $cadences
     * @return array{start_date: string, end_date: string|null}
     */
    private static function alignToCadence(array $operation, array $current, Collection $cadences): array
    {
        $edges = $operation['edges'] ?? 'both';
        $threshold = $operation['threshold_days'] ?? self::DEFAULT_ALIGN_THRESHOLD_DAYS;
        $named = isset($operation['cadence_id']) ? $cadences->firstWhere('id', $operation['cadence_id']) : null;

        // A term can span more than one cadence, in which case measuring both edges
        // against the start's term produces nonsense. With no `cadence_id` each edge
        // resolves its own nearest boundary, which is what the dock's Align button sends.
        $startCadence = $named ?? self::nearest($cadences, $current['start_date']);
        $endCadence = $named ?? ($current['end_date'] !== null
            ? self::nearest($cadences, $current['end_date'])
            : null);

        if (($edges === 'start' || $edges === 'both') && $startCadence !== null) {
            $current['start_date'] = self::alignedOr(
                $current['start_date'], $startCadence->start_date->toDateString(), $threshold
            );
        }

        // An open-ended row is closed by `close_open_ended`, not by aligning — aligning
        // is about drift on a date that already exists.
        if (($edges === 'end' || $edges === 'both') && $current['end_date'] !== null && $endCadence !== null) {
            $current['end_date'] = self::alignedOr(
                $current['end_date'], $endCadence->end_date->toDateString(), $threshold
            );
        }

        return $current;
    }

    /**
     * The cadence whose window contains the date, else the one whose start sits closest
     * to it — the same order {@see ResolveCadenceForDuty::pick()} uses.
     *
     * @param  Collection<int, Cadence>  $cadences
     */
    private static function nearest(Collection $cadences, string $date): ?Cadence
    {
        if ($cadences->isEmpty()) {
            return null;
        }

        $at = Carbon::parse($date);

        return $cadences->first(fn (Cadence $cadence) => $cadence->contains($at))
            ?? $cadences->sortBy(
                fn (Cadence $cadence) => $cadence->start_date->diffInDays($at, absolute: true)
            )->first();
    }

    /**
     * Snaps only when the gap is small enough to read as drift; a genuinely different
     * date is left alone so a bulk align cannot quietly rewrite an intentional term.
     */
    private static function alignedOr(string $date, string $target, ?int $thresholdDays): string
    {
        if ($thresholdDays === null) {
            return $target;
        }

        return Carbon::parse($date)->diffInDays(Carbon::parse($target), absolute: true) <= $thresholdDays
            ? $target
            : $date;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array{start_date: string, end_date: string|null}  $current
     * @return array{start_date: string, end_date: string|null}
     */
    private static function closeOpenEnded(array $operation, array $current): array
    {
        if ($current['end_date'] !== null) {
            return $current;
        }

        $current['end_date'] = $operation['end_date'];

        return $current;
    }

    /**
     * @param  Collection<string, Dutiable>  $rows
     * @param  array<string, array{start_date: string, end_date: string|null}>  $original
     * @param  array<string, array{start_date: string, end_date: string|null}>  $state
     * @param  array<string, list<string>>  $reasons
     * @param  Collection<int, Cadence>  $cadences
     */
    private static function buildPlan(
        Collection $rows,
        array $original,
        array $state,
        array $reasons,
        Collection $cadences,
    ): DutiableTimelinePlan {
        $changes = [];
        $unchanged = [];

        foreach ($rows as $id => $row) {
            if ($state[$id] === $original[$id]) {
                $unchanged[] = $id;

                continue;
            }

            $changes[] = new DutiableTimelineChange(
                rowId: $id,
                holderId: $row->dutiable_id,
                holderName: $row->user?->name,
                dutyName: $row->duty?->name,
                before: $original[$id],
                after: $state[$id],
                reasons: array_values(array_unique($reasons[$id])),
                derived: self::projectDerived($row, $state[$id]),
                blocked: self::blockReason($row, $state[$id]),
            );
        }

        // The point of the delta is the headline the diff sheet leads with: whether the
        // batch actually removed the drift it was for. Measured over the same rows both
        // times, so the two counts are comparable.
        $writable = array_filter(
            $state,
            fn (array $dates, string $rowId) => ($rows->get($rowId)?->via_dutiable_id) === null,
            ARRAY_FILTER_USE_BOTH,
        );

        // `$rows` is keyed by id for the applier; the analyzer only ever iterates, so it
        // takes the list form.
        $analyzable = $rows->values();

        return new DutiableTimelinePlan(
            $changes,
            $unchanged,
            $rows,
            AnalyzeDutiableTimeline::execute($analyzable, $cadences),
            AnalyzeDutiableTimeline::execute($analyzable, $cadences, $writable),
        );
    }

    /**
     * Every cadence the touched duties could be measured against: the institution
     * ladders in play plus the global one they fall back to.
     *
     * @param  Collection<string, Dutiable>  $rows
     * @return Collection<int, Cadence>
     */
    private static function cadencesFor(Collection $rows): Collection
    {
        $institutionIds = $rows->map(fn (Dutiable $row) => $row->duty?->institution_id)
            ->filter()
            ->unique()
            ->values();

        return Cadence::query()
            ->where(function ($query) use ($institutionIds): void {
                $query->whereNull('institution_id');

                if ($institutionIds->isNotEmpty()) {
                    $query->orWhereIn('institution_id', $institutionIds->all());
                }
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * An institution that defines its own cadences never falls back to the global ladder —
     * same rule as ResolveCadenceForDuty and AnalyzeDutiableTimeline.
     *
     * @param  Collection<int, Cadence>  $cadences
     * @return Collection<int, Cadence>
     */
    private static function applicable(Collection $cadences, ?Dutiable $row): Collection
    {
        $institutionId = $row?->duty?->institution_id;

        $scoped = $institutionId === null
            ? collect()
            : $cadences->where('institution_id', $institutionId);

        return $scoped->isNotEmpty() ? $scoped->values() : $cadences->whereNull('institution_id')->values();
    }

    /**
     * Ex-officio rows mirror their source, exactly as SyncExOfficioDutiables::syncDerivedRow()
     * does after the queue drains. Projecting it here is what lets the diff show the
     * knock-on seats before anything is written.
     *
     * @param  array{start_date: string, end_date: string|null}  $after
     * @return list<array{id: string, duty_name: string|null, start_date: string, end_date: string|null}>
     */
    private static function projectDerived(Dutiable $row, array $after): array
    {
        if ($row->via_dutiable_id !== null) {
            return [];
        }

        return $row->derivedDutiables
            ->map(fn (Dutiable $derived) => [
                'id' => $derived->id,
                'duty_name' => $derived->duty?->name,
                'start_date' => $after['start_date'],
                'end_date' => $after['end_date'],
            ])
            ->values()
            ->all();
    }

    /**
     * Reported rather than dropped, so a bulk selection can say what it skipped.
     *
     * - `derived`: an ex-officio row's dates are not its own; DutiableController::update()
     *   strips edits to them for the same reason.
     * - `inverted`: no combination of operations may leave a row ending before it begins.
     *   Nothing at the database level forbids it, so the guard belongs here, where every
     *   write path already passes.
     *
     * @param  array{start_date: string, end_date: string|null}  $after
     */
    private static function blockReason(Dutiable $row, array $after): ?string
    {
        if ($row->via_dutiable_id !== null) {
            return 'derived';
        }

        if ($after['end_date'] !== null && $after['end_date'] < $after['start_date']) {
            return 'inverted';
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     * @return list<string>
     */
    private static function targetedRowIds(array $operations): array
    {
        $ids = [];

        foreach ($operations as $operation) {
            foreach ($operation['row_ids'] as $rowId) {
                $ids[$rowId] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * @param  list<string>  $rowIds
     * @return Collection<string, Dutiable>
     */
    private static function loadRows(array $rowIds): Collection
    {
        if ($rowIds === []) {
            return collect();
        }

        return Dutiable::query()
            ->without('study_program')
            ->with(['duty:id,name,institution_id', 'user:id,name', 'derivedDutiables.duty:id,name'])
            ->whereIn('id', $rowIds)
            ->get()
            ->keyBy('id');
    }
}
