<?php

namespace App\Enums;

/**
 * One layer of a managed list's membership. Every duty-based source counts only assignments
 * in force on the resolution date.
 */
enum MailingListSourceType: string
{
    case Duty = 'duty';
    case DutyType = 'duty_type';
    case Institution = 'institution';
    case InstitutionType = 'institution_type';
    /** Every duty holding the responsibility (`source_id` is the role id), however it was granted. */
    case Responsibility = 'responsibility';
    case MailingList = 'mailing_list';
    case Email = 'email';

    public function usesAddressMode(): bool
    {
        return ! in_array($this, [self::MailingList, self::Email], true);
    }
}
