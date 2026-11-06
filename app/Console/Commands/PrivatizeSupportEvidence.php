<?php

namespace App\Console\Commands;

use App\Jobs\Media\PrivatizeSupportEvidence as TransferEvidence;
use App\Services\Media\SupportEvidenceTransfer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Sleep;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Transitional(private-evidence): remove when media:privatize-evidence --status reports no pending files or public copies. */
#[Description('Copy support evidence and previews to private storage, optionally removing verified public copies')]
#[Signature('media:privatize-evidence
    {--status : Report remaining transfers and public copies}
    {--sync : Run in the foreground}
    {--detach : Queue without following progress}
    {--cleanup : Remove verified public copies after transfer}
    {--force : Skip the public-copy deletion confirmation}')]
class PrivatizeSupportEvidence extends Command
{
    public function handle(SupportEvidenceTransfer $transfer): int
    {
        $records = $transfer->records()->get();
        $pending = $records->filter($transfer->pending(...));
        $copies = $records->filter($transfer->hasPublicCopies(...));

        if ($this->option('status')) {
            $this->table(['Pending transfers', 'Media with public copies'], [[$pending->count(), $copies->count()]]);

            return self::SUCCESS;
        }

        $cleanup = (bool) $this->option('cleanup');
        $records = $records->filter(fn (Media $media): bool => $transfer->pending($media) || ($cleanup && $transfer->hasPublicCopies($media)));
        if ($cleanup && ! $this->option('force')) {
            $files = $records->flatMap(fn (Media $media): array => array_map(
                fn (array $file): array => [$media->id, $file['disk'], $file['path']],
                $transfer->cleanupFiles($media),
            ));

            if ($files->isNotEmpty()) {
                $this->table(['Media', 'Disk', 'Public file to delete'], $files->all());
                if (! $this->input->isInteractive()) {
                    $this->error('Public-copy cleanup requires confirmation or --force.');

                    return self::FAILURE;
                }

                if (! $this->confirm("Delete {$files->count()} public evidence file(s)?")) {
                    $this->info('Public-copy cleanup cancelled. No files were changed.');

                    return self::SUCCESS;
                }
            }
        }

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        if ($this->option('sync')) {
            foreach ($records as $media) {
                $transfer->transfer($media->id, $cleanup);
                $bar->advance();
            }
        } elseif ($records->isNotEmpty()) {
            $batch = Bus::batch($records->map(fn (Media $media) => new TransferEvidence($media->id, $cleanup))->all())
                ->onConnection('long-running')->allowFailures()->dispatch();
            $this->info("Queued {$batch->totalJobs} jobs (batch {$batch->id}).");

            if (! $this->option('detach')) {
                do {
                    $batch = $batch->fresh();
                    $bar->setProgress($batch->totalJobs - $batch->pendingJobs + $batch->failedJobs);
                    if ($batch->finished() || $batch->cancelled() || $batch->pendingJobs <= $batch->failedJobs) {
                        break;
                    }
                    Sleep::for(2)->seconds();
                } while (true);

                if ($batch->failedJobs > 0) {
                    $this->error("{$batch->failedJobs} transfers failed. Rerun to retry remaining media.");

                    return self::FAILURE;
                }
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Check remaining transfers and public copies with media:privatize-evidence --status.');

        return self::SUCCESS;
    }
}
