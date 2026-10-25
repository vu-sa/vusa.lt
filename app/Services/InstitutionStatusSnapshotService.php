<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionStatusSnapshot;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Support\InstitutionStatusTally;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Closed days are immutable; only explicitly reconstructed rows may be repaired. */
class InstitutionStatusSnapshotService
{
    public function __construct(private readonly InstitutionStatusReplayService $replay) {}

    /**
     * @return int the number of padalinys-days written
     */
    public function capture(CarbonInterface $from, CarbonInterface $to, bool $reconstructed, bool $repair = false, ?callable $progress = null): int
    {
        return Cache::lock('visak:status-capture', 600)->block(10, fn (): int => $this->captureRange($from, $to, $reconstructed, $repair, $progress));
    }

    private function captureRange(CarbonInterface $from, CarbonInterface $to, bool $reconstructed, bool $repair, ?callable $progress): int
    {
        $first = CarbonImmutable::instance($from)->startOfDay();
        $last = CarbonImmutable::instance($to)->startOfDay()->min(today()->subDay()->toImmutable());

        if ($first->gt($last)) {
            return 0;
        }

        $tenantIds = Tenant::query()->representational()->pluck('id')->all();
        /** @var Collection<string, Collection<int, InstitutionStatusSnapshot>> $existing */
        $existing = InstitutionStatusSnapshot::query()->whereBetween('taken_on', [$first->subDay()->toDateString(), $last->toDateString()])
            ->whereIn('tenant_id', $tenantIds)->get()->groupBy(fn ($row) => $row->taken_on->toDateString())
            ->map(fn ($rows) => $rows->keyBy('tenant_id'));
        $days = [];
        for ($day = $first; $day->lte($last); $day = $day->addDay()) {
            $rows = $existing->get($day->toDateString(), collect());
            if ($rows->count() !== count($tenantIds) || ($repair && $rows->contains('is_reconstructed', true))) {
                $days[] = $day;
            } else {
                if ($progress !== null) {
                    $progress();
                }
            }
        }
        if ($days === []) {
            return 0;
        }

        $institutions = $this->replay->institutionsForTenants($tenantIds)->groupBy('tenant_id');
        $summaries = [];
        foreach ($tenantIds as $tenantId) {
            $summaries[$tenantId] = $this->summarizeDays($institutions->get($tenantId, collect()), $days);
        }
        $written = 0;

        foreach ($days as $day) {
            $written += $this->storeDay($day, $tenantIds, $summaries, $existing, $reconstructed, $repair);
            if ($progress !== null) {
                $progress();
            }
        }

        if ($written > 0) {
            RepStatisticsCache::invalidate(history: true);
        }

        return $written;
    }

    /** @param Collection<string, Collection<int, InstitutionStatusSnapshot>> $existing */
    private function storeDay(CarbonImmutable $day, array $tenantIds, array $summaries, Collection $existing, bool $reconstructed, bool $repair): int
    {
        $date = $day->toDateString();
        $rows = $existing->get($date, collect());
        $previous = $existing->get($day->subDay()->toDateString(), collect());
        [$count, $stored] = DB::transaction(function () use ($day, $date, $tenantIds, $summaries, $rows, $previous, $reconstructed, $repair): array {
            $count = 0;
            $records = [];
            $stored = [];
            foreach ($tenantIds as $tenantId) {
                $original = $rows->get($tenantId);
                if ($original !== null && (! $repair || ! $original->is_reconstructed)) {
                    continue;
                }
                $row = $original === null
                    ? new InstitutionStatusSnapshot(['taken_on' => $date, 'tenant_id' => $tenantId])
                    : clone $original;
                $summary = $summaries[$tenantId][$date];
                $before = $previous->get($tenantId)?->institution_statuses;
                if ($before !== null) {
                    $summary = [...$summary, ...$this->transitions(
                        array_map(InstitutionActivityStatus::from(...), $before),
                        array_map(InstitutionActivityStatus::from(...), $summary['institution_statuses']),
                    )];
                }
                $row->fill([...$summary, 'is_reconstructed' => $reconstructed || ! $day->isYesterday()]);
                if (! $row->exists || $row->isDirty()) {
                    $row->updateTimestamps();
                    $attributes = $row->getAttributes();
                    unset($attributes['id']);
                    $records[] = $attributes;
                }
                $stored[$tenantId] = $row;
                $count++;
            }
            foreach (array_chunk($records, 250) as $batch) {
                InstitutionStatusSnapshot::query()->upsert($batch, ['taken_on', 'tenant_id'], [
                    ...InstitutionStatusSnapshot::SUMMED_COLUMNS, 'institution_statuses', 'meeting_ids',
                    'is_reconstructed', 'updated_at',
                ]);
            }

            return [$count, $stored];
        });
        $existing->put($date, $rows->replace($stored));

        return $count;
    }

    /**
     * @param  Collection<int, Institution>  $institutions  prepared with {@see InstitutionStatusReplayService::prepare()}
     * @param  list<CarbonImmutable>  $days  consecutive, ascending
     * @return array<string, array{all: int, needs_attention: int, overdue: int, approaching: int, no_activity: int, recent_check_in: int, current: int, meetings_held: int, meetings_registered: int, check_ins_registered: int, entered_attention: int, left_attention: int, institution_statuses: array<string, string>, meeting_ids: array{held: list<string>, registered: list<string>}, population_added: int, population_removed: int}> date => summary
     */
    public function summarizeDays(Collection $institutions, array $days): array
    {
        if ($days === []) {
            return [];
        }

        $moments = [];
        foreach ($days as $day) {
            $moments[] = $day->subDay()->endOfDay();
            $moments[] = $day->endOfDay()->min(now()->toImmutable());
        }
        $statusesByMoment = $this->replay->replay($institutions, $moments);

        $flows = $this->flowBuckets($institutions, $days);
        $summaries = [];

        foreach ($days as $index => $day) {
            $summaries[$day->toDateString()] = [
                ...InstitutionStatusTally::fromStatuses($statusesByMoment[$index * 2 + 1]),
                ...$flows[$day->toDateString()],
                ...$this->transitions($statusesByMoment[$index * 2], $statusesByMoment[$index * 2 + 1]),
                'institution_statuses' => array_map(fn (InstitutionActivityStatus $status): string => $status->value, $statusesByMoment[$index * 2 + 1]),
            ];
        }

        return $summaries;
    }

    /** @param Collection<int, Institution> $institutions */
    private function flowBuckets(Collection $institutions, array $days): array
    {
        $ends = [];
        foreach ($days as $day) {
            $ends[$day->toDateString()] = $day->endOfDay()->min(now()->toImmutable());
        }
        $held = $registered = $checkIns = [];
        foreach ($institutions as $institution) {
            foreach ($institution->meetings as $meeting) {
                /** @var Meeting $meeting */
                $heldOn = $meeting->start_time->toDateString();
                $end = $ends[$heldOn] ?? null;
                if ($end !== null && $meeting->start_time->lte($end) && $meeting->created_at->lte($end)
                    && ($meeting->deleted_at === null || $meeting->deleted_at->gt($end))) {
                    $held[$heldOn][(string) $meeting->id] = true;
                }
                $registeredOn = $meeting->created_at->toDateString();
                $end = $ends[$registeredOn] ?? null;
                if ($end !== null && $meeting->created_at->lte($end)) {
                    $registered[$registeredOn][(string) $meeting->id] = true;
                }
            }
            foreach ($institution->checkIns as $checkIn) {
                $date = $checkIn->created_at->toDateString();
                $end = $ends[$date] ?? null;
                if ($end !== null && $checkIn->created_at->lte($end)) {
                    $checkIns[$date] = ($checkIns[$date] ?? 0) + 1;
                }
            }
        }
        $buckets = [];
        foreach ($days as $day) {
            $date = $day->toDateString();
            $buckets[$date] = [
                'meetings_held' => count($held[$date] ?? []),
                'meetings_registered' => count($registered[$date] ?? []),
                'meeting_ids' => ['held' => array_keys($held[$date] ?? []), 'registered' => array_keys($registered[$date] ?? [])],
                'check_ins_registered' => $checkIns[$date] ?? 0,
            ];
        }

        return $buckets;
    }

    public function transitions(array $before, array $after): array
    {
        $entered = $left = 0;

        foreach ($after as $id => $status) {
            if (! isset($before[$id])) {
                continue;
            }
            $wasNeeding = $before[$id]->requiresAction();
            $isNeeding = $status->requiresAction();

            $entered += (int) (! $wasNeeding && $isNeeding);
            $left += (int) ($wasNeeding && ! $isNeeding);
        }

        return [
            'entered_attention' => $entered,
            'left_attention' => $left,
            'population_added' => count(array_diff_key($after, $before)),
            'population_removed' => count(array_diff_key($before, $after)),
        ];
    }
}
