<?php

namespace App\Console\Commands;

use App\Actions\FetchMailingListArchive;
use App\Models\MailingList;
use App\Services\Mailman\HyperkittyClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Copy new archived posts from HyperKitty for every mailing list')]
#[Signature('mailing-lists:fetch-archives')]
class FetchMailingListArchivesCommand extends Command
{
    public function handle(HyperkittyClient $hyperkitty, FetchMailingListArchive $fetch): int
    {
        if (! $hyperkitty->isConfigured()) {
            $this->warn('HyperKitty is not configured.');

            return self::SUCCESS;
        }

        $failed = 0;

        MailingList::query()->each(function (MailingList $mailingList) use ($fetch, &$failed): void {
            try {
                $fetch->execute($mailingList);
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("{$mailingList->address}: {$exception->getMessage()}");
            }
        });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
