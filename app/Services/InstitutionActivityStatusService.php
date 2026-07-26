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
    private const APPROACHING_RATIO = 0.8;

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

        $nextMeetingAt = $institution->meetings
            ->filter(fn (Meeting $meeting): bool => $meeting->start_time->gt($moment))
            ->min(fn (Meeting $meeting) => $meeting->start_time->getTimestamp());

        $nextMeeting = $nextMeetingAt !== null
            ? $institution->meetings->first(
                fn (Meeting $meeting): bool => $meeting->start_time->getTimestamp() === $nextMeetingAt
            )
            : null;

        $activeCheckIn = $institution->checkIns
            ->first(fn (InstitutionCheckIn $checkIn): bool => $checkIn->start_date->lte($today)
                && $checkIn->end_date->gte($today));

        $latestCompletedCheckIn = $institution->checkIns
            ->filter(fn (InstitutionCheckIn $checkIn): bool => $checkIn->end_date->lt($today))
            ->sortByDesc(fn (InstitutionCheckIn $checkIn): int => $checkIn->end_date->getTimestamp())
            ->first();

        $lastMeetingAt = $institution->meetings
            ->filter(fn (Meeting $meeting): bool => $meeting->start_time->lte($moment))
            ->max(fn (Meeting $meeting) => $meeting->start_time->getTimestamp());

        $lastMeeting = $lastMeetingAt !== null
            ? $institution->meetings->first(
                fn (Meeting $meeting): bool => $meeting->start_time->getTimestamp() === $lastMeetingAt
            )
            : null;

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
        $activityCalculationStart = $checkInIsLatestActivity
            ? $completedCheckInEnd->addDay()
            : $lastMeetingDate;
        $effectiveDays = $activityCalculationStart
            ? $this->academicCalendar->effectiveDaysBetween($activityCalculationStart, $moment)
            : null;
        $progressPercentage = $effectiveDays !== null
            ? (int) round(($effectiveDays / $periodicityDays) * 100)
            : null;

        $status = match (true) {
            $nextMeeting !== null => InstitutionActivityStatus::CoveredByUpcomingMeeting,
            $activeCheckIn !== null => InstitutionActivityStatus::CoveredByCheckIn,
            $lastActivityAt === null => InstitutionActivityStatus::NoActivity,
            $effectiveDays > $periodicityDays => InstitutionActivityStatus::Overdue,
            ($effectiveDays / $periodicityDays) >= self::APPROACHING_RATIO => InstitutionActivityStatus::Approaching,
            default => InstitutionActivityStatus::Healthy,
        };

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
        );
    }
}
