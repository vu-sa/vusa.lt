<?php

namespace App\Actions\ActivityRequests;

use App\Enums\ActivityRequestSkipReason;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Services\AcademicCalendarService;
use App\Services\InstitutionActivityStatusService;
use App\ValueObjects\InstitutionActivityStatusData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ResolveActivityRequestEligibility
{
    public function __construct(
        private readonly InstitutionActivityStatusService $statuses,
        private readonly AcademicCalendarService $calendar,
        private readonly ResolveActivityRequestRecipients $recipients,
    ) {}

    public function status(Institution $institution, ?CarbonInterface $tenureStart = null, ?CarbonInterface $at = null): InstitutionActivityStatusData
    {
        $moment = CarbonImmutable::instance($at ?? now());
        $status = $this->statuses->resolve($institution, $moment);
        if ($status->status !== InstitutionActivityStatus::NoActivity || $tenureStart === null) {
            return $status;
        }

        $days = $tenureStart->gt($moment) ? 0 : $this->calendar->effectiveDaysBetween($tenureStart, $moment);

        return new InstitutionActivityStatusData(
            status: $this->statuses->classify(false, false, $days, null, $status->periodicityDays),
            periodicityDays: $status->periodicityDays,
            effectiveDaysSinceActivity: $days,
            progressPercentage: (int) round($days / $status->periodicityDays * 100),
        );
    }

    public function skipReason(Institution $institution, ?CarbonInterface $tenureStart = null, ?CarbonInterface $at = null, bool $manual = false): ?ActivityRequestSkipReason
    {
        if (! $institution->is_active) {
            return ActivityRequestSkipReason::InactiveInstitution;
        }

        // With nothing on record there is no deadline to respect: only the automatic question waits out a term.
        if ($manual && $this->statuses->resolve($institution, $at)->status === InstitutionActivityStatus::NoActivity) {
            return null;
        }

        return match ($this->status($institution, $tenureStart, $at)->status) {
            InstitutionActivityStatus::Overdue => null,
            InstitutionActivityStatus::RecentCheckIn => $manual ? null : ActivityRequestSkipReason::RecentActivity,
            InstitutionActivityStatus::CoveredByUpcomingMeeting => ActivityRequestSkipReason::UpcomingMeeting,
            InstitutionActivityStatus::CoveredByCheckIn => ActivityRequestSkipReason::CheckInActive,
            default => ActivityRequestSkipReason::RecentActivity,
        };
    }

    public function shouldDeliver(InstitutionActivityRequest $request): bool
    {
        $institution = $request->institution;
        if (! $request->isOpen() || $institution === null || ! $institution->is_active || $request->recipient === null) {
            return false;
        }
        if ($request->campaign_type === InstitutionActivityCampaign::MissingMeetings) {
            return $request->incompleteMeetings()->isNotEmpty();
        }

        return $this->skipReason($institution, $this->heldSince($request), manual: $request->requested_by_id !== null) === null
            && ! $institution->meetings->contains(fn ($meeting) => $meeting->start_time->toDateString() >= $request->period_start->toDateString()
                && $meeting->start_time->toDateString() <= $request->periodEnd()->toDateString());
    }

    /** Must match what the send used: period_start is clamped to the term and would drop the question. */
    private function heldSince(InstitutionActivityRequest $request): CarbonInterface
    {
        $roles = $this->recipients->forInstitutions(collect([$request->institution_id]))[$request->institution_id];

        return ($roles['secretaries'][$request->recipient_id] ?? $roles['representatives'][$request->recipient_id] ?? null)['held_since']
            ?? $request->period_start;
    }
}
