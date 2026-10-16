<?php

namespace App\Enums;

/**
 * Whether the app owns a list's membership. Unmanaged lists are only mirrored: the app reads
 * their roster, stats and archive but never writes to Mailman.
 */
enum MailingListMode: string
{
    case Unmanaged = 'unmanaged';
    case Managed = 'managed';
}
