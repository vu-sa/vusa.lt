<?php

namespace App\Actions\Dutiables;

use App\Actions\CollapseOverlappingDutiables;
use App\Listeners\SyncExOfficioDutiables;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\Dutiables\DutiablePeriods;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Applies selected review repairs atomically after checking freshness and permissions.
 *
 * @phpstan-import-type ReviewItem from PlanExOfficioReview
 */
class ApplyExOfficioReview
{
    public static function execute(Duty $duty, User $actor, array $data): void
    {
        DB::transaction(function () use ($duty, $actor, $data): void {
            Gate::forUser($actor)->authorize('managePeople', $duty);
            $targetIds = PlanExOfficioReview::targetIds($duty);
            $duties = Duty::query()
                ->whereKey($targetIds)
                ->orWhereHas('exOfficioTargetDuties', fn ($query) => $query->whereKey($targetIds))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            Institution::withTrashed()
                ->whereKey($duties->pluck('institution_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $rows = Dutiable::query()
                ->with(['duty.institution', 'user'])
                ->whereIn('duty_id', $duties->modelKeys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $plan = PlanExOfficioReview::execute($targetIds, $actor);
            abort_unless(hash_equals($plan['fingerprint'], $data['fingerprint']), 409);
            $items = collect($plan['items'])->keyBy('id');
            self::validateSelection($items, $rows, $data);
            foreach ($data['item_ids'] as $id) {
                $item = $items[$id];
                $source = $rows[$item['source_id']];
                $target = $duties->find($item['target_duty_id']);
                $row = $item['row_id'] === null ? null : $rows[$item['row_id']];
                self::authorizeItem($item, $source, $target, $row, $rows, $actor);
                abort_unless(app(DutyAssignmentService::class)->lockExOfficioContext($source, $target), 409);
                $suppressed = self::applyItem(
                    $item, $source, $target, $row, $rows, $actor, $data['remainder_choices'][$id] ?? 'end'
                );
                self::log($row ?? $source, $actor, $item, $suppressed);
            }
        });
    }

    private static function validateSelection(Collection $items, Collection $rows, array $data): void
    {
        foreach ($data['item_ids'] as $id) {
            abort_unless($items->has($id), 422);
            abort_unless(array_diff($items[$id]['requires'], $data['item_ids']) === [], 422);
        }
        $selected = $items->only($data['item_ids']);
        self::validateDuplicateCoverage($selected, $rows);
        foreach ($data['remainder_choices'] ?? [] as $id => $choice) {
            abort_unless(in_array($id, $data['item_ids'], true) && $items[$id]['orphan'], 422);
        }
    }

    private static function validateDuplicateCoverage(Collection $selected, Collection $rows): void
    {
        foreach ($selected->where('kind', 'remove_duplicate') as $duplicate) {
            $candidate = $rows[$duplicate['row_id']];
            $coverage = collect($duplicate['survivor_ids'])->map(function (string $seatId) use ($rows, $selected): Dutiable {
                $seat = clone $rows[$seatId];
                if ($selected->where('kind', 'resync')->contains('row_id', $seatId)) {
                    $source = $rows[$seat->via_dutiable_id];
                    $seat->start_date = $source->start_date;
                    $seat->end_date = $source->end_date;
                }

                return $seat;
            });
            abort_unless(DutiablePeriods::remainders($candidate, $coverage, 0) === [], 422);
        }
    }

    /** @param ReviewItem $item */
    private static function authorizeItem(
        array $item,
        Dutiable $source,
        Duty $target,
        ?Dutiable $row,
        Collection $rows,
        User $actor,
    ): void {
        $touchedRows = collect($item['source_ids'])
            ->map(fn (string $sourceId) => $rows[$sourceId])
            ->push($row)
            ->filter()
            ->merge(collect($item['survivor_ids'])->map(fn (string $seatId) => $rows[$seatId]))
            ->unique('id');
        foreach ($touchedRows as $touched) {
            Gate::forUser($actor)->authorize('manageDutiable', $touched);
        }
        Gate::forUser($actor)->authorize('managePeople', [$target, $source->user]);
    }

    /** @param ReviewItem $item */
    private static function applyItem(
        array $item,
        Dutiable $source,
        Duty $target,
        ?Dutiable $row,
        Collection $rows,
        User $actor,
        string $choice,
    ): array {
        switch ($item['kind']) {
            case 'remove_duplicate':
                self::removeDuplicate($item, $row, $rows);
                break;
            case 'create':
                self::create($item, $source, $target, $actor);
                break;
            case 'link':
                return self::link($item, $row, $source, $actor, $choice);
            default:
                self::resync($item, $row);
        }

        return [];
    }

    /** @param ReviewItem $item */
    private static function removeDuplicate(array $item, Dutiable $row, Collection $rows): void
    {
        foreach ($item['survivor_ids'] as $seatId) {
            CollapseOverlappingDutiables::copyDetails($rows[$seatId], $row);
        }
        $row->delete();
    }

    /** @param ReviewItem $item */
    private static function create(array $item, Dutiable $source, Duty $target, User $actor): void
    {
        app(SyncExOfficioDutiables::class)->syncDerivedRow($source, $source->dutiable_id, $target);
        $created = Dutiable::query()
            ->where('via_dutiable_id', $source->id)
            ->where('duty_id', $target->id)
            ->firstOrFail();
        Gate::forUser($actor)->authorize('manageDutiable', $created);
        self::log($created, $actor, $item, $source->end_date?->lt(today()) ? [
            'started' => $source->start_date->toDateString(), 'ended' => $source->end_date->copy()->addDay()->toDateString(),
        ] : []);
    }

    /** @param ReviewItem $item */
    private static function link(array $item, Dutiable $row, Dutiable $source, User $actor, string $choice): array
    {
        $suppressed = [];
        $details = $row->only([
            'duty_id', 'dutiable_id', 'dutiable_type', 'tenant_id', 'study_program_id',
            'additional_email', 'use_original_duty_name',
        ]);
        foreach (['description', 'study_program_note'] as $field) {
            $details[$field] = $row->getTranslations($field);
        }
        foreach ($item['remainders'] as $period) {
            if ($item['orphan'] && $choice === 'end'
                && $period['start_date'] > $source->end_date->toDateString()) {
                continue;
            }
            $remainder = new Dutiable([...$details, ...$period, 'assignment_origin' => 'manual']);
            Gate::forUser($actor)->authorize('manageDutiable', $remainder);
            $remainder->save();
            // The linked part ending where a kept remainder begins is not a change in access.
            if ($source->end_date !== null && $period['start_date'] > $source->end_date->toDateString()) {
                $suppressed['ended'] = $period['start_date'];
            } else {
                $suppressed['started'] = $source->start_date->toDateString();
            }
            self::log($remainder, $actor, $item, [
                'started' => $period['start_date'] === $item['before']['start_date'] ? null : $period['start_date'],
                'ended' => $period['end_date'] === null || $period['end_date'] === $item['before']['end_date']
                    ? null
                    : Carbon::parse($period['end_date'])->addDay()->toDateString(),
            ]);
        }
        LinkDutiableToExOfficioSource::execute($row, $source);

        return $suppressed;
    }

    /** @param ReviewItem $item */
    private static function resync(array $item, Dutiable $row): void
    {
        $row->update([...$item['after'], 'tenant_id' => $item['tenant_after']]);
    }

    private static function log(Dutiable $row, User $actor, array $item, array $suppressed = []): void
    {
        activity()->performedOn($row)
            ->causedBy($actor)
            ->event('ex_officio_reviewed')
            ->withProperties(['item' => $item['id'], 'suppressed_access_changes' => $suppressed])
            ->log('ex_officio_reviewed');
    }
}
