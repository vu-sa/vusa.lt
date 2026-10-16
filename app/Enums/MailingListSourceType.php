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
    case MailingList = 'mailing_list';
    case Email = 'email';

    public function usesAddressMode(): bool
    {
        return ! in_array($this, [self::MailingList, self::Email], true);
    }
}
