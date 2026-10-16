<?php

namespace App\Enums;

enum MailingListAddressMode: string
{
    /** The assignment's own email, else the holder's main email. */
    case Holder = 'holder';

    /** The duty's role mailbox (duties.email), so the list follows the role on handover. */
    case DutyMailbox = 'duty_mailbox';
}
