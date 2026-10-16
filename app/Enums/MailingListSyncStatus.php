<?php

namespace App\Enums;

enum MailingListSyncStatus: string
{
    case Synced = 'synced';

    /** The sync would remove so much that it waits for someone to apply it by hand. */
    case NeedsReview = 'needs_review';

    case Failed = 'failed';
}
