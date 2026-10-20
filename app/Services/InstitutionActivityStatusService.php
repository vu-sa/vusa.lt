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

        $lastMeetingDate = $lastMeeting?->start_time->toImmutable();
        $completedCheckInEnd = $latestCompletedCheckIn?->end_date->toImmutable()->startOfDay();
        $meetingDays = $lastMeetingDate
            ? $this->academicCalendar->effectiveDaysBetween($lastMeetingDate, $moment)
            : null;
        $checkInDays = $completedCheckInEnd
            ? $this->checkInDaysSince($completedCheckInEnd, $moment)
            : null;
        [$lastActivityType, $lastActivityAt, $effectiveDays, $thresholdDays] = $this->activityReference(
            $lastMeetingDate, $completedCheckInEnd, $meetingDays, $checkInDays, $periodicityDays,
        );
        $progressPercentage = $effectiveDays !== null
            ? (int) round(($effectiveDays / $thresholdDays) * 100)
            : null;

        $status = $this->classify(
            hasUpcomingMeeting: $nextMeeting !== null,
            hasActiveCheckIn: $activeCheckIn !== null,
            effectiveMeetingDays: $meetingDays,
            effectiveCheckInDays: $checkInDays,
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
            lastCompletedCheckInAt: $completedCheckInEnd,
            nextMeetingAt: $nextMeeting?->start_time->toImmutable(),
            activeCheckInUntil: $activeCheckIn?->end_date->toImmutable(),
        );
    }

    /** @return array{?string, ?CarbonImmutable, ?int, int} */
    private function activityReference(
        ?CarbonImmutable $meetingAt,
        ?CarbonImmutable $checkInEnd,
        ?int $meetingDays,
        ?int $checkInDays,
        int $periodicityDays,
    ): array {
        $meetingCoversToday = $meetingDays !== null && $meetingDays <= $periodicityDays;
        $graceDays = $this->checkInGraceDays($periodicityDays);

        if ($checkInEnd !== null && ! $meetingCoversToday
            && ($checkInDays <= $graceDays || $meetingAt === null || $checkInEnd->endOfDay()->gt($meetingAt))) {
            return ['check_in', $checkInEnd, $checkInDays, $graceDays];
        }

        return [$meetingAt === null ? null : 'meeting', $meetingAt, $meetingDays, $periodicityDays];
    }

    public function checkInGraceDays(int $periodicityDays): int
    {
        return max(1, (int) ceil($periodicityDays / 2));
    }

    public function checkInDaysSince(CarbonInterface $end, CarbonInterface $at): int
    {
        // Coverage includes its end date; grace counts the days after it through today.
        return $this->academicCalendar->effectiveDaysBetween(
            CarbonImmutable::instance($end)->addDay(), CarbonImmutable::instance($at)->addDay(),
        );
    }

    /** The history sweep must use the same coverage and threshold rules as the live status. */
    public function classify(
        bool $hasUpcomingMeeting,
        bool $hasActiveCheckIn,
        ?int $effectiveMeetingDays,
        ?int $effectiveCheckInDays,
        int $periodicityDays,
    ): InstitutionActivityStatus {
        return match (true) {
            $hasUpcomingMeeting => InstitutionActivityStatus::CoveredByUpcomingMeeting,
            $hasActiveCheckIn => InstitutionActivityStatus::CoveredByCheckIn,
            $effectiveMeetingDays !== null && $effectiveMeetingDays <= $periodicityDays => ($effectiveMeetingDays / $periodicityDays) >= self::APPROACHING_RATIO
                    ? InstitutionActivityStatus::Approaching
                    : InstitutionActivityStatus::Healthy,
            $effectiveCheckInDays !== null && $effectiveCheckInDays <= $this->checkInGraceDays($periodicityDays) => InstitutionActivityStatus::RecentCheckIn,
            $effectiveMeetingDays === null && $effectiveCheckInDays === null => InstitutionActivityStatus::NoActivity,
            default => InstitutionActivityStatus::Overdue,
        };
    }
}
