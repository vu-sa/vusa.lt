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
        $existing = InstitutionStatusSnapshot::query()->whereBetween('taken_on', [$first->toDateString(), $last->toDateString()])
            ->whereIn('tenant_id', $tenantIds)->get()->groupBy(fn ($row) => $row->taken_on->toDateString());
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
            $written += $this->storeDay($day, $tenantIds, $summaries, $reconstructed, $repair);
            if ($progress !== null) {
                $progress();
            }
        }

        if ($written > 0) {
            RepStatisticsCache::invalidate(history: true);
        }

        return $written;
    }

    private function storeDay(CarbonImmutable $day, array $tenantIds, array $summaries, bool $reconstructed, bool $repair): int
    {
        return DB::transaction(function () use ($day, $tenantIds, $summaries, $reconstructed, $repair): int {
            $count = 0;
            foreach ($tenantIds as $tenantId) {
                $row = InstitutionStatusSnapshot::query()->firstOrNew(['taken_on' => $day->toDateString(), 'tenant_id' => $tenantId]);
                if ($row->exists && (! $repair || ! $row->is_reconstructed)) {
                    continue;
                }
                $summary = $summaries[$tenantId][$day->toDateString()];
                $previous = InstitutionStatusSnapshot::query()->where('tenant_id', $tenantId)->whereDate('taken_on', $day->subDay())->first();
                if ($previous?->institution_statuses !== null) {
                    $before = array_map(InstitutionActivityStatus::from(...), $previous->institution_statuses);
                    $after = array_map(InstitutionActivityStatus::from(...), $summary['institution_statuses']);
                    $summary = [...$summary, ...$this->transitions($before, $after)];
                }
                $row->fill([...$summary, 'is_reconstructed' => $reconstructed || ! $day->isYesterday()])->save();
                $count++;
            }

            return $count;
        });
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

        $summaries = [];

        foreach ($days as $index => $day) {
            $summaries[$day->toDateString()] = [
                ...InstitutionStatusTally::fromStatuses($statusesByMoment[$index * 2 + 1]),
                ...$this->flow($institutions, $day, $statusesByMoment[$index * 2], $statusesByMoment[$index * 2 + 1]),
                'institution_statuses' => array_map(fn (InstitutionActivityStatus $status): string => $status->value, $statusesByMoment[$index * 2 + 1]),
            ];
        }

        return $summaries;
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @param  array<int|string, InstitutionActivityStatus>  $before  statuses at the end of the previous day
     * @param  array<int|string, InstitutionActivityStatus>  $after  statuses at the end of $day
     * @return array{meetings_held: int, meetings_registered: int, check_ins_registered: int, entered_attention: int, left_attention: int, meeting_ids: array{held: list<string>, registered: list<string>}, population_added: int, population_removed: int}
     */
    private function flow(Collection $institutions, CarbonImmutable $day, array $before, array $after): array
    {
        $end = $day->endOfDay()->min(now()->toImmutable());
        $held = $registered = [];
        $checkIns = 0;

        foreach ($institutions as $institution) {
            foreach ($institution->meetings as $meeting) {
                /** @var Meeting $meeting */
                $knownAtEnd = $meeting->created_at->lte($end) && ($meeting->deleted_at === null || $meeting->deleted_at->gt($end));

                if ($knownAtEnd && $meeting->start_time->isSameDay($day) && $meeting->start_time->lte($end)) {
                    $held[(string) $meeting->id] = true;
                }

                if ($meeting->created_at->isSameDay($day) && $meeting->created_at->lte($end)) {
                    $registered[(string) $meeting->id] = true;
                }
            }

            $checkIns += $institution->checkIns->filter(fn ($checkIn) => $checkIn->created_at->isSameDay($day) && $checkIn->created_at->lte($end))->count();
        }

        return [
            'meetings_held' => count($held),
            'meetings_registered' => count($registered),
            'meeting_ids' => ['held' => array_keys($held), 'registered' => array_keys($registered)],
            'check_ins_registered' => $checkIns,
            ...$this->transitions($before, $after),
        ];
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
