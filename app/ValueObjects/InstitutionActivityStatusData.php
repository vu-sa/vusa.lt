<?php

namespace App\ValueObjects;

use App\Enums\InstitutionActivityStatus;
use Carbon\CarbonImmutable;

readonly class InstitutionActivityStatusData
{
    public function __construct(
        public InstitutionActivityStatus $status,
        public int $periodicityDays,
        public ?int $effectiveDaysSinceActivity = null,
        public ?int $progressPercentage = null,
        public ?string $lastActivityType = null,
        public ?CarbonImmutable $lastActivityAt = null,
        public ?CarbonImmutable $lastMeetingAt = null,
        public ?CarbonImmutable $nextMeetingAt = null,
        public ?CarbonImmutable $activeCheckInUntil = null,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     requires_action: bool,
     *     priority: int,
     *     periodicity_days: int,
     *     effective_days_since_activity: int|null,
     *     progress_percentage: int|null,
     *     last_activity_type: string|null,
     *     last_activity_at: string|null,
     *     last_meeting_at: string|null,
     *     next_meeting_at: string|null,
     *     active_check_in_until: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'requires_action' => $this->status->requiresAction(),
            'priority' => $this->status->priority(),
            'periodicity_days' => $this->periodicityDays,
            'effective_days_since_activity' => $this->effectiveDaysSinceActivity,
            'progress_percentage' => $this->progressPercentage,
            'last_activity_type' => $this->lastActivityType,
            'last_activity_at' => $this->lastActivityAt?->toISOString(),
            'last_meeting_at' => $this->lastMeetingAt?->toISOString(),
            'next_meeting_at' => $this->nextMeetingAt?->toISOString(),
            'active_check_in_until' => $this->activeCheckInUntil?->toDateString(),
        ];
    }
}
