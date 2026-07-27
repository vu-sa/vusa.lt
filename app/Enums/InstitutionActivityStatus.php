<?php

namespace App\Enums;

enum InstitutionActivityStatus: string
{
    case NoActivity = 'no_activity';
    case Healthy = 'healthy';
    case Approaching = 'approaching';
    case Overdue = 'overdue';
    case CoveredByUpcomingMeeting = 'covered_by_upcoming_meeting';
    case CoveredByCheckIn = 'covered_by_check_in';

    public function requiresAction(): bool
    {
        return match ($this) {
            self::NoActivity, self::Approaching, self::Overdue => true,
            self::Healthy, self::CoveredByUpcomingMeeting, self::CoveredByCheckIn => false,
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::Overdue => 50,
            self::NoActivity => 40,
            self::Approaching => 30,
            self::CoveredByCheckIn => 20,
            self::CoveredByUpcomingMeeting => 10,
            self::Healthy => 0,
        };
    }
}
