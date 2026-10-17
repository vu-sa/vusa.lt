<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\ValueObjects\InstitutionActivityStatusData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class InstitutionActivityStatusService
{
    private const float APPROACHING_RATIO = 0.8;

    public function __construct(
        private readonly AcademicCalendarService $academicCalendar,
    ) {}

    public function resolve(
        Institution $institution,
        ?CarbonInterface $at = null,
    ): InstitutionActivityStatusData {
        $institution->loadMissing(['meetings', 'checkIns', 'types']);

        $moment = $at
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::now();
        $today = $moment->startOfDay();
        $periodicityDays = $institution->meeting_periodicity_days;

        $nextMeeting = $institution->meetings
            ->filter(fn (Meeting $meeting): bool => $meeting->start_time->gt($moment))
            ->sortBy(fn (Meeting $meeting): int => $meeting->start_time->getTimestamp())
            ->first();

        $activeCheckIn = $institution->checkIns
            ->first(fn (InstitutionCheckIn $checkIn): bool => $checkIn->start_date->lte($today)
                && $checkIn->end_date->gte($today));

        $latestCompletedCheckIn = $institution->checkIns
            ->filter(fn (InstitutionCheckIn $checkIn): bool => $checkIn->end_date->lt($today))
            ->sortByDesc(fn (InstitutionCheckIn $checkIn): int => $checkIn->end_date->getTimestamp())
            ->first();

        $lastMeeting = $institution->meetings
            ->filter(fn (Meeting $meeting): bool => $meeting->start_time->lte($moment))
            ->sortByDesc(fn (Meeting $meeting): int => $meeting->start_time->getTimestamp())
            ->first();

        $lastMeetingDate = $lastMeeting
            ? CarbonImmutable::instance($lastMeeting->start_time)
            : null;

        $completedCheckInEnd = $latestCompletedCheckIn
            ? CarbonImmutable::instance($latestCompletedCheckIn->end_date)->startOfDay()
            : null;
        $checkInIsLatestActivity = $completedCheckInEnd !== null
            && ($lastMeetingDate === null || $completedCheckInEnd->endOfDay()->gt($lastMeetingDate));
        $lastActivityType = match (true) {
            $checkInIsLatestActivity => 'check_in',
            $lastMeetingDate !== null => 'meeting',
            default => null,
        };
        $lastActivityAt = $checkInIsLatestActivity
            ? $completedCheckInEnd
            : $lastMeetingDate;
        $effectiveDays = $this->countedDays(
            $lastMeetingDate,
            $institution->checkIns
                ->map(fn (InstitutionCheckIn $checkIn): array => [
                    'start' => CarbonImmutable::instance($checkIn->start_date)->startOfDay(),
                    'end' => CarbonImmutable::instance($checkIn->end_date)->startOfDay(),
                ])
                ->values()
                ->all(),
            $moment,
        );
        $progressPercentage = $effectiveDays !== null
            ? (int) round(($effectiveDays / $periodicityDays) * 100)
            : null;

        $status = $this->classify(
            hasUpcomingMeeting: $nextMeeting !== null,
            hasActiveCheckIn: $activeCheckIn !== null,
            hasActivity: $lastActivityAt !== null,
            effectiveDays: $effectiveDays ?? 0,
            periodicityDays: $periodicityDays,
        );

        return new InstitutionActivityStatusData(
            status: $status,
            periodicityDays: $periodicityDays,
            effectiveDaysSinceActivity: $effectiveDays,
            progressPercentage: $progressPercentage,
            lastActivityType: $lastActivityType,
            lastActivityAt: $lastActivityAt,
            lastMeetingAt: $lastMeetingDate,
            nextMeetingAt: $nextMeeting
                ? CarbonImmutable::instance($nextMeeting->start_time)
                : null,
            activeCheckInUntil: $activeCheckIn
                ? CarbonImmutable::instance($activeCheckIn->end_date)
                : null,
            lastCheckInStart: $checkInIsLatestActivity
                ? CarbonImmutable::instance($latestCompletedCheckIn->start_date)
                : null,
            lastCheckInEnd: $checkInIsLatestActivity ? $completedCheckInEnd : null,
        );
    }

    /**
     * Days counted towards the periodicity: from the last meeting (or, with none, the first
     * check-in), skipping vacations and every day a check-in covered. A check-in pauses the
     * clock; it never restarts it, so it cannot hide an uncovered gap before it began.
     *
     * Shared with AtstovavimasDashboardService's status-history sweep. Null when there is
     * neither a meeting nor a check-in that has started.
     *
     * @param  list<array{start: CarbonImmutable, end: CarbonImmutable}>  $checkIns  inclusive days
     */
    public function countedDays(?CarbonImmutable $lastMeeting, array $checkIns, CarbonInterface $moment): ?int
    {
        $today = CarbonImmutable::instance($moment)->startOfDay();
        $started = array_values(array_filter($checkIns, fn (array $checkIn): bool => $checkIn['start']->lte($today)));

        $from = $lastMeeting ?? array_reduce(
            $started,
            fn (?CarbonImmutable $earliest, array $checkIn): CarbonImmutable => $earliest === null || $checkIn['start']->lt($earliest) ? $checkIn['start'] : $earliest,
        );

        if ($from === null) {
            return null;
        }

        return $this->academicCalendar->effectiveDaysBetween($from, $moment, $started);
    }

    /**
     * The pure decision rule behind an institution's activity status, shared by
     * resolve() and AtstovavimasDashboardService's status-history sweep — the
     * latter derives hasUpcomingMeeting/hasActiveCheckIn/hasActivity itself (for
     * performance, across many dates) and effectiveDays through countedDays().
     * $effectiveDays is ignored when $hasActivity is false.
     */
    public function classify(
        bool $hasUpcomingMeeting,
        bool $hasActiveCheckIn,
        bool $hasActivity,
        int $effectiveDays,
        int $periodicityDays,
    ): InstitutionActivityStatus {
        return match (true) {
            $hasUpcomingMeeting => InstitutionActivityStatus::CoveredByUpcomingMeeting,
            $hasActiveCheckIn => InstitutionActivityStatus::CoveredByCheckIn,
            ! $hasActivity => InstitutionActivityStatus::NoActivity,
            $effectiveDays > $periodicityDays => InstitutionActivityStatus::Overdue,
            ($effectiveDays / $periodicityDays) >= self::APPROACHING_RATIO => InstitutionActivityStatus::Approaching,
            default => InstitutionActivityStatus::Healthy,
        };
    }
}
