<?php

namespace App\Actions\Dutiables;

use App\Actions\ResolveExOfficioTenantId;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\Dutiables\DutiablePeriods;
use App\Support\Dutiables\ExOfficioReviewDependencies;
use App\Support\MorphMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Plans safe repairs for overlapping and out-of-sync ex-officio assignments.
 *
 * @phpstan-type Period array{start_date: string, end_date: string|null}
 * @phpstan-type BlockedItem array{source_id: string, holder_name: string|null, target_duty_id: string, reason: string}
 * @phpstan-type ReviewItem array{
 *     id: string, kind: string, source_id: string, row_id: string|null, target_duty_id: string,
 *     holder_id: string, holder_name: string|null, duty_name: array|string|null,
 *     before: Period|null, after: Period, tenant_before: int|null, tenant_after: int|null,
 *     quota_change: bool, tenant_before_name: string|null, tenant_after_name: string|null,
 *     access_change: bool, orphan: bool, remainders: list<Period>, survivor_ids: list<string>,
 *     source_ids: list<string>, survivors: list<Period>, selected: bool,
 *     requires?: list<string>, unavailable_dependency?: bool
 * }
 * @phpstan-type ReviewPlan array{
 *     items: list<ReviewItem>, blocked: list<BlockedItem>, candidate_ids: list<string>,
 *     matches: list<array{row_id: string, source_duty_id: string}>, fingerprint: string
 * }
 */
class PlanExOfficioReview
{
    public const int MIN_OVERLAP_DAYS = 7;

    public static function targetIds(Duty $duty): array
    {
        return $duty->exOfficioTargetDuties()
            ->pluck('duties.id')
            ->push($duty->id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * The plan as one duty's page sees it: pairs where the duty is the source or the target.
     * A shared target has many sources, and other units' terms are not this duty's to review.
     *
     * @return ReviewPlan
     */
    public static function forDuty(Duty $duty, ?User $actor = null): array
    {
        $plan = self::execute(self::targetIds($duty), $actor);
        $sourceDuties = Dutiable::query()
            ->whereKey([...array_column($plan['items'], 'source_id'), ...array_column($plan['blocked'], 'source_id')])
            ->pluck('duty_id', 'id');
        $inScope = fn (array $entry): bool => $entry['target_duty_id'] === $duty->id
            || $sourceDuties->get($entry['source_id']) === $duty->id;
        $itemsById = collect($plan['items'])->keyBy('id');
        $pending = $itemsById->filter($inScope)->keys()->all();
        $kept = [];

        // A proposal stays complete: what it requires comes along, even from another source.
        while ($pending !== []) {
            $id = array_pop($pending);
            if (! isset($kept[$id])) {
                $kept[$id] = true;
                array_push($pending, ...$itemsById[$id]['requires']);
            }
        }

        return [
            ...$plan,
            'items' => $itemsById->only(array_keys($kept))->values()->all(),
            'blocked' => array_values(array_filter($plan['blocked'], $inScope)),
            'candidate_ids' => [],
            'matches' => [],
        ];
    }

    /** @return ReviewPlan */
    public static function execute(array $targetDutyIds, ?User $actor = null): array
    {
        $targets = Duty::query()
            ->with(['institution', 'assignableTenants', 'exOfficioSourceDuties.institution'])
            ->whereKey($targetDutyIds)
            ->orderBy('id')
            ->get();
        $dutyIds = $targets->pluck('exOfficioSourceDuties')
            ->flatten()
            ->pluck('id')
            ->merge($targets->modelKeys())
            ->unique();
        $rows = Dutiable::query()
            ->with([
                'duty.institution', 'tenant', 'user', 'derivedDutiables', 'media',
                'viaDutiable.duty.institution', 'viaDutiable.user',
            ])
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('duty_id', $dutyIds)
            ->orderBy('id')
            ->get();
        $items = [];
        $blocked = [];
        $candidateIds = [];
        $matchesForQuery = [];

        foreach ($targets as $target) {
            if (! $target->institution?->is_active) {
                continue;
            }
            self::planTarget($target, $rows, $actor, $items, $blocked, $candidateIds, $matchesForQuery);
        }

        $resolved = ExOfficioReviewDependencies::resolve($items, $rows, $targets);
        $items = $resolved['items'];
        $blocked = array_merge($blocked, $resolved['blocked']);

        return [
            'items' => $items, 'blocked' => $blocked, 'candidate_ids' => array_values(array_unique($candidateIds)),
            'matches' => $matchesForQuery,
            'fingerprint' => hash('sha256', json_encode([
                today()->toDateString(),
                $targets->toArray(),
                $rows->map(fn (Dutiable $row) => [
                    $row->getAttributes(),
                    $row->duty?->institution?->getAttributes(),
                    $row->media->toArray(),
                    $row->derivedDutiables->map->getAttributes()->all(),
                ])->all(),
                $items,
                $blocked,
            ])),
        ];
    }

    private static function planTarget(
        Duty $target,
        Collection $rows,
        ?User $actor,
        array &$items,
        array &$blocked,
        array &$candidateIds,
        array &$matchesForQuery,
    ): void {
        $sourceIds = $target->exOfficioSourceDuties
            ->filter(fn (Duty $duty): bool => (bool) $duty->institution?->is_active)
            ->pluck('id')
            ->all();
        $sources = $rows->whereIn('duty_id', $sourceIds)
            ->where('ended_by_institution_deactivation', false)
            ->filter(fn (Dutiable $source): bool => $source->user !== null);
        $targetRows = $rows->where('duty_id', $target->id)->where('ended_by_institution_deactivation', false);
        $candidates = $targetRows->whereNull('via_dutiable_id')->where('assignment_origin', 'unknown');
        $assigned = [];
        $duplicates = [];
        self::matchCandidates($candidates, $sources, $targetRows, $assigned, $duplicates, $candidateIds, $matchesForQuery);
        $used = [];
        foreach ($sources as $source) {
            $candidate = self::candidateForSource($source, $candidates, $assigned, $duplicates);
            self::planSource($source, $target, $rows, $sources, $candidate, $actor, $items, $blocked, $used);
        }
        foreach ($candidates->whereNotIn('id', $used) as $candidate) {
            if (! isset($duplicates[$candidate->id])) {
                continue;
            }
            $cover = $duplicates[$candidate->id];
            $seat = $cover->sortBy('id')->first();
            $source = $sources->firstWhere('id', $seat->via_dutiable_id);
            self::propose($items, $blocked, 'remove_duplicate', $source, $target, $candidate, [], $actor, $cover);
        }
        // Partly covered by a linked term: no safe automatic fix, so say so rather than stay silent.
        $partialCandidates = $candidates->whereIn('id', array_keys($assigned))
            ->whereNotIn('id', $used)
            ->whereNotIn('id', array_keys($duplicates));
        foreach ($partialCandidates as $candidate) {
            $blocked[] = self::blocked($candidate, $target, 'partial');
        }
    }

    private static function matchCandidates(
        Collection $candidates,
        Collection $sources,
        Collection $targetRows,
        array &$assigned,
        array &$duplicates,
        array &$candidateIds,
        array &$matchesForQuery,
    ): void {
        foreach ($candidates as $candidate) {
            $matches = $sources->filter(fn (Dutiable $source): bool => $source->via_dutiable_id === null
                && $source->dutiable_id === $candidate->dutiable_id
                && DutiablePeriods::overlapDays($candidate, $source) > self::MIN_OVERLAP_DAYS)
                ->sortBy(fn (Dutiable $source) => [-DutiablePeriods::overlapDays($candidate, $source), $source->id]);
            if ($matches->isNotEmpty()) {
                $candidateIds[] = $candidate->id;
                foreach ($matches as $match) {
                    $matchesForQuery[] = ['row_id' => $candidate->id, 'source_duty_id' => $match->duty_id];
                }
                $assigned[$candidate->id] = $matches->first()->id;
                $cover = $targetRows->whereNotNull('via_dutiable_id')
                    ->where('assignment_origin', '!=', 'manual')
                    ->filter(fn (Dutiable $seat): bool => $seat->dutiable_id === $candidate->dutiable_id
                        && DutiablePeriods::overlapDays($candidate, $seat) > 0
                        && $sources->whereNull('via_dutiable_id')->contains('id', $seat->via_dutiable_id));
                if ($cover->isNotEmpty() && DutiablePeriods::remainders($candidate, $cover, 0) === []) {
                    $duplicates[$candidate->id] = $cover;
                }
            }
        }
    }

    private static function candidateForSource(
        Dutiable $source,
        Collection $candidates,
        array $assigned,
        array $duplicates,
    ): ?Dutiable {
        return $candidates
            ->filter(fn (Dutiable $row): bool => ($assigned[$row->id] ?? null) === $source->id
                && ! isset($duplicates[$row->id]))
            ->sortBy(fn (Dutiable $row) => [-DutiablePeriods::overlapDays($row, $source), $row->id])
            ->first();
    }

    private static function planSource(
        Dutiable $source,
        Duty $target,
        Collection $rows,
        Collection $sources,
        ?Dutiable $candidate,
        ?User $actor,
        array &$items,
        array &$blocked,
        array &$used,
    ): void {
        $linked = $rows->where('duty_id', $target->id)->where('via_dutiable_id', $source->id);
        if ($source->via_dutiable_id !== null) {
            $blocked[] = self::blocked($source, $target, 'chain');

            return;
        }
        if ($source->user === null) {
            return;
        }
        if ($actor !== null && (! $actor->can('manageDutiable', $source) || ! $actor->can('managePeople', [$target, $source->user]))) {
            $blocked[] = self::blocked($source, $target, 'permission');

            return;
        }
        if ($linked->isNotEmpty()) {
            self::planResyncs($linked, $source, $target, $actor, $items, $blocked);

            return;
        }
        if ($candidate !== null) {
            $used[] = $candidate->id;
            $periods = self::linkRemainders($candidate, $source, $sources);
            self::propose($items, $blocked, 'link', $source, $target, $candidate, $periods, $actor);
        } else {
            self::propose($items, $blocked, 'create', $source, $target, null, [], $actor);
        }
    }

    private static function planResyncs(
        Collection $linked,
        Dutiable $source,
        Duty $target,
        ?User $actor,
        array &$items,
        array &$blocked,
    ): void {
        foreach ($linked as $seat) {
            if ($seat->assignment_origin === 'manual' || $seat->ended_by_institution_deactivation) {
                continue;
            }
            if (DutiablePeriods::dates($seat) !== DutiablePeriods::dates($source)
                || $seat->tenant_id !== ResolveExOfficioTenantId::execute($source, $target)) {
                self::propose($items, $blocked, 'resync', $source, $target, $seat, [], $actor);
            }
        }
    }

    /** @return list<Period> */
    private static function linkRemainders(Dutiable $candidate, Dutiable $source, Collection $sources): array
    {
        $periods = DutiablePeriods::remainders($candidate, $sources->filter(
            fn (Dutiable $row): bool => $row->via_dutiable_id === null && $row->dutiable_id === $candidate->dutiable_id
        ), 0);
        $periods = array_values(array_filter($periods, fn (array $period) => $period['end_date'] === null
            || Carbon::parse($period['start_date'])->diffInDays(Carbon::parse($period['end_date'])) + 1 > self::MIN_OVERLAP_DAYS
            || (DutiablePeriods::current($candidate) && $source->end_date?->lt(today())
                && $period['start_date'] <= today()->toDateString()
                && $period['end_date'] >= today()->toDateString())));

        return $periods;
    }

    /**
     * @param  list<ReviewItem>  $items
     * @param  list<BlockedItem>  $blocked
     * @param  list<Period>  $remainders
     */
    private static function propose(
        array &$items,
        array &$blocked,
        string $kind,
        Dutiable $source,
        Duty $target,
        ?Dutiable $row,
        array $remainders,
        ?User $actor,
        ?Collection $cover = null,
    ): void {
        $sourceIds = collect([$source->id])
            ->merge($cover?->pluck('via_dutiable_id') ?? [])
            ->unique()
            ->values()
            ->all();
        $otherSources = collect([$source])
            ->merge($cover?->map(fn (Dutiable $seat) => $seat->viaDutiable)->filter() ?? []);
        $touched = collect([$row])->filter()->merge($otherSources)->merge($cover ?? []);
        if ($row?->derivedDutiables->isNotEmpty()) {
            $blocked[] = self::blocked($row, $target, 'chain');

            return;
        }
        if ($actor !== null && $touched->contains(fn (Dutiable $seat) => ! $actor->can('manageDutiable', $seat))) {
            $blocked[] = self::blocked($source, $target, 'permission');

            return;
        }
        $items[] = self::makeItem($kind, $source, $target, $row, $remainders, $cover, $sourceIds);
    }

    /**
     * @param  list<Period>  $remainders
     * @param  list<string>  $sourceIds
     * @return ReviewItem
     */
    private static function makeItem(
        string $kind,
        Dutiable $source,
        Duty $target,
        ?Dutiable $row,
        array $remainders,
        ?Collection $cover,
        array $sourceIds,
    ): array {
        $tenant = ResolveExOfficioTenantId::execute($source, $target);
        $orphan = self::isOrphan($kind, $row, $source);
        $accessChange = self::changesAccess($kind, $row, $source);
        $quotaChange = $row !== null && $row->tenant_id !== $tenant;

        return [
            'id' => $kind.':'.$target->id.':'.$source->id.':'.($row->id ?? 'new'),
            'kind' => $kind, 'source_id' => $source->id, 'row_id' => $row?->id, 'target_duty_id' => $target->id,
            'holder_id' => $source->dutiable_id, 'holder_name' => $source->user?->name, 'duty_name' => $target->name,
            'before' => $row === null ? null : DutiablePeriods::dates($row), 'after' => DutiablePeriods::dates($source),
            'tenant_before' => $row?->tenant_id, 'tenant_after' => $tenant, 'quota_change' => $quotaChange,
            'tenant_before_name' => $row?->tenant?->shortname,
            'tenant_after_name' => $target->assignableTenants->firstWhere('id', $tenant)?->shortname,
            'access_change' => $accessChange, 'orphan' => $orphan, 'remainders' => $remainders,
            'survivor_ids' => $cover?->pluck('id')->all() ?? [], 'source_ids' => $sourceIds,
            'survivors' => $cover?->map(fn (Dutiable $seat) => DutiablePeriods::dates($seat))
                ->values()->all() ?? [],
            'selected' => ! $accessChange && ! $quotaChange && ! $orphan && $kind !== 'remove_duplicate',
        ];
    }

    private static function isOrphan(string $kind, ?Dutiable $row, Dutiable $source): bool
    {
        return $kind === 'link' && $row !== null && DutiablePeriods::current($row)
            && $source->end_date !== null && $source->end_date->lt(today());
    }

    private static function changesAccess(string $kind, ?Dutiable $row, Dutiable $source): bool
    {
        return $kind === 'create'
            ? DutiablePeriods::current($source)
            : ($row !== null && DutiablePeriods::current($row) !== DutiablePeriods::current($source));
    }

    /** @return BlockedItem */
    private static function blocked(Dutiable $source, Duty $target, string $reason): array
    {
        return ['source_id' => $source->id, 'holder_name' => $source->user?->name, 'target_duty_id' => $target->id, 'reason' => $reason];
    }
}
