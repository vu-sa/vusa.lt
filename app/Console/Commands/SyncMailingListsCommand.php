<?php

namespace App\Console\Commands;

use App\Jobs\SyncManagedMailingLists;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Terms end on a date without any save, so no DutiableChanged fires; this daily run is what
 * removes people whose duty ended yesterday.
 */
#[Description('Bring every managed mailing list in line with current duties')]
#[Signature('mailing-lists:sync')]
class SyncMailingListsCommand extends Command
{
    public function handle(): int
    {
        SyncManagedMailingLists::dispatchSync();

        return self::SUCCESS;
    }
}
