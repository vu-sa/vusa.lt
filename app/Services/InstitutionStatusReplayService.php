<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/** Reconstructs registration/deletion knowledge; institution settings and deleted check-ins have no history. */
class InstitutionStatusReplayService
{
    public function __construct(
        private readonly InstitutionActivityStatusService $activityStatusService,
        private readonly AcademicCalendarService $academicCalendar,
    ) {}

    /**
     * The counted institutions of the given tenants with what the replay reads. Independent of
     * the request user, so the nightly snapshot can use it.
     *
     * @param  list<int>  $tenantIds
     * @return Collection<int, Institution>
     */
    public function institutionsForTenants(array $tenantIds): Collection
    {
        return $this->prepare(
            RepStatisticsScope::institutions($tenantIds)
                ->get(['id', 'tenant_id', 'is_active', 'meeting_periodicity_days'])
        );
    }

    /**
     * Loads meetings (including trashed and registration times) and check-ins.
     *
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, Institution>
     */
    public function prepare(Collection $institutions): Collection
    {
        return $institutions->load([
            'types',
            'meetings' => fn ($query) => $query->withTrashed()->select('meetings.id', 'start_time', 'meetings.created_at', 'meetings.deleted_at'),
            'checkIns',
        ]);
    }

    /**
     * @param  Collection<int, Institution>  $institutions  prepared with {@see prepare()}
     * @param  list<CarbonInterface>  $moments  the instants to classify at, usually end of day
     * @return list<array<int|string, InstitutionActivityStatus>> per moment, institution id => status
     */
    public function replay(\Illuminate\Support\Collection $institutions, array $moments): array
    {
        $timelines = $institutions->mapWithKeys(fn (Institution $institution): array => [
            $institution->getKey() => [
                'meetings' => $institution->meetings->map(fn (Meeting $meeting): array => [
                    'start' => CarbonImmutable::instance($meeting->start_time),
                    'registered' => $meeting->created_at->getTimestamp(),
                    'deleted' => $meeting->deleted_at?->getTimestamp(),
                ])->all(),
                'checkIns' => $institution->checkIns->map(fn (InstitutionCheckIn $checkIn): array => [
                    'start' => CarbonImmutable::instance($checkIn->start_date)->startOfDay(),
                    'end' => CarbonImmutable::instance($checkIn->end_date)->startOfDay(),
                    'registered' => $checkIn->created_at->getTimestamp(),
                ])->all(),
                'periodicity' => $institution->meeting_periodicity_days,
            ],
        ]);

        $series = [];

        foreach ($moments as $moment) {
            $momentTs = $moment->getTimestamp();
            $day = CarbonImmutable::instance($moment)->startOfDay();
            $statuses = [];

            foreach ($timelines as $id => $timeline) {
                $statuses[$id] = $this->statusAt($timeline, $moment, $momentTs, $day);
            }

            $series[] = $statuses;
        }

        return $series;
    }

    /**
     * @param  array{meetings: list<array{start: CarbonImmutable, registered: int, deleted: int|null}>, checkIns: list<array{start: CarbonImmutable, end: CarbonImmutable, registered: int}>, periodicity: int}  $timeline
     */
    private function statusAt(array $timeline, CarbonInterface $moment, int $momentTs, CarbonImmutable $day): InstitutionActivityStatus
    {
        [$hasUpcoming, $lastMeeting] = $this->meetingsAt($timeline['meetings'], $momentTs);
        [$activeCheckIn, $latestCompletedEnd] = $this->checkInsAt($timeline['checkIns'], $momentTs, $day);

        return $this->activityStatusService->classify(
            hasUpcomingMeeting: $hasUpcoming,
            hasActiveCheckIn: $activeCheckIn,
            effectiveMeetingDays: $lastMeeting ? $this->academicCalendar->effectiveDaysBetween($lastMeeting, $moment) : null,
            effectiveCheckInDays: $latestCompletedEnd ? $this->activityStatusService->checkInDaysSince($latestCompletedEnd, $moment) : null,
            periodicityDays: $timeline['periodicity'],
        );
    }

    private function meetingsAt(array $meetings, int $momentTs): array
    {
        $hasUpcoming = false;
        $lastMeeting = null;

        foreach ($meetings as $meeting) {
            if ($meeting['registered'] > $momentTs || ($meeting['deleted'] !== null && $meeting['deleted'] <= $momentTs)) {
                continue;
            }

            if ($meeting['start']->getTimestamp() > $momentTs) {
                $hasUpcoming = true;
            } elseif ($lastMeeting === null || $meeting['start']->gt($lastMeeting)) {
                $lastMeeting = $meeting['start'];
            }
        }

        return [$hasUpcoming, $lastMeeting];
    }

    private function checkInsAt(array $checkIns, int $momentTs, CarbonImmutable $day): array
    {
        $activeCheckIn = false;
        $latestCompletedEnd = null;

        foreach ($checkIns as $checkIn) {
            if ($checkIn['registered'] > $momentTs) {
                continue;
            }

            if ($checkIn['start']->lte($day) && $checkIn['end']->gte($day)) {
                $activeCheckIn = true;
            }

            if ($checkIn['end']->lt($day) && ($latestCompletedEnd === null || $checkIn['end']->gt($latestCompletedEnd))) {
                $latestCompletedEnd = $checkIn['end'];
            }
        }

        return [$activeCheckIn, $latestCompletedEnd];
    }
}
