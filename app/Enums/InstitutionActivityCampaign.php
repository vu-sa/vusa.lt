<?php

namespace App\Enums;

enum InstitutionActivityCampaign: string
{
    case ActivityConfirmation = 'activity_confirmation';
    case MissingMeetings = 'missing_meetings';

    public function labelKey(): string
    {
        return match ($this) {
            self::ActivityConfirmation => 'activity_requests.campaigns.activity_confirmation',
            self::MissingMeetings => 'activity_requests.campaigns.missing_meetings',
        };
    }

    public function bodyKey(): string
    {
        return match ($this) {
            self::ActivityConfirmation => 'activity_requests.email.activity_confirmation',
            self::MissingMeetings => 'activity_requests.email.missing_meetings',
        };
    }
}
