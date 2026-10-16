<?php

namespace App\Console\Commands;

use App\Actions\ImportMailingLists;
use App\Services\Mailman\MailmanClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Mirror every Mailman list into the app (new lists arrive unmanaged)')]
#[Signature('mailing-lists:import')]
class ImportMailingListsCommand extends Command
{
    public function handle(MailmanClient $mailman, ImportMailingLists $import): int
    {
        if (! $mailman->isConfigured()) {
            $this->warn('Mailman is not configured.');

            return self::SUCCESS;
        }

        $result = $import->execute();
        $this->info("Imported: {$result['created']} new, {$result['updated']} updated.");

        return self::SUCCESS;
    }
}
