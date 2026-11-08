<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\ValueObjects\InstitutionActivityStatusData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class InstitutionActivityStatusService
{
    private const float APPROACHING_RATIO = 0.8;

    public function __construct(
        private readonly AcademicCalendarService $academicCalendar,
    ) {}

    /** @param  Builder<Institution>  $query */
    public function withSummary(Builder $query): Builder
    {
        $meeting = fn () => Meeting::query()->select('start_time')
            ->join('institution_meeting', 'meetings.id', '=', 'institution_meeting.meeting_id')
            ->whereColumn('institution_meeting.institution_id', 'institutions.id')
            ->limit(1);

        $latestCompletedEnd = InstitutionCheckIn::query()
            ->from('institution_check_ins as completed_check_ins')
            ->select('completed_check_ins.end_date')
            ->whereColumn('completed_check_ins.institution_id', 'institution_check_ins.institution_id')
            ->whereDate('completed_check_ins.end_date', '<', today())
            ->orderByDesc('completed_check_ins.end_date')->limit(1);

        return $query->addSelect([
            'activity_next_meeting_at' => $meeting()->where('start_time', '>', now())->orderBy('start_time'),
            'activity_last_meeting_at' => $meeting()->where('start_time', '<=', now())->orderByDesc('start_time'),
        ])->with([
            'types',
            'checkIns' => fn ($checkIns) => $checkIns
                ->select('id', 'institution_id', 'start_date', 'end_date')
                ->where(fn ($relevant) => $relevant
                    ->where(fn ($active) => $active->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today()))
                    ->orWhere('end_date', $latestCompletedEnd)),
        ]);
    }

    public function resolve(
        Institution $institution,
        ?CarbonInterface $at = null,
    ): InstitutionActivityStatusData {
        $hasSummary = $at === null && array_key_exists('activity_next_meeting_at', $institution->getAttributes());
        $institution->loadMissing($hasSummary ? ['checkIns', 'types'] : ['meetings', 'checkIns', 'types']);

        $moment = $at
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::now();
        $today = $moment->startOfDay();
        $periodicityDays = $institution->meeting_periodicity_days;

        $nextMeetingDate = $hasSummary
            ? ($institution->getAttribute('activity_next_meeting_at') === null ? null : CarbonImmutable::parse($institution->getAttribute('activity_next_meeting_at')))
            : $institution->meetings
                ->filter(fn (Meeting $meeting): bool => $meeting->start_time->gt($moment))
                ->sortBy(fn (Meeting $meeting): int => $meeting->start_time->getTimestamp())
                ->first()?->start_time->toImmutable();

        $activeCheckIn = $institution->checkIns
            ->first(fn (InstitutionCheckIn $checkIn): bool => $checkIn->start_date->lte($today)
                && $checkIn->end_date->gte($today));

        $latestCompletedCheckIn = $institution->checkIns
            ->filter(fn (InstitutionCheckIn $checkIn): bool => $checkIn->end_date->lt($today))
            ->sortByDesc(fn (InstitutionCheckIn $checkIn): int => $checkIn->end_date->getTimestamp())
            ->first();

        $lastMeetingDate = $hasSummary
            ? ($institution->getAttribute('activity_last_meeting_at') === null ? null : CarbonImmutable::parse($institution->getAttribute('activity_last_meeting_at')))
            : $institution->meetings
                ->filter(fn (Meeting $meeting): bool => $meeting->start_time->lte($moment))
                ->sortByDesc(fn (Meeting $meeting): int => $meeting->start_time->getTimestamp())
                ->first()?->start_time->toImmutable();
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
            hasUpcomingMeeting: $nextMeetingDate !== null,
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
            nextMeetingAt: $nextMeetingDate,
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
