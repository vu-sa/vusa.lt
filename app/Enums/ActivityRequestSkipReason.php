<?php

namespace App\Enums;

/** Why an "Ar vyko posėdis?" question is not sent to an institution or one of its recipients. */
enum ActivityRequestSkipReason: string
{
    case NotAllowed = 'not_allowed';
    case NoRecipients = 'no_recipients';
    case NoRepresentativeDuties = 'no_representative_duties';
    case NoPeriod = 'no_period';
    case InactiveInstitution = 'inactive_institution';
    case UpcomingMeeting = 'upcoming_meeting';
    case RecentActivity = 'recent_activity';
    case NoRecordedMeetings = 'no_recorded_meetings';
    case RecordsComplete = 'records_complete';
    case CheckInActive = 'check_in_active';
    case AlreadyAsked = 'already_asked';
}
