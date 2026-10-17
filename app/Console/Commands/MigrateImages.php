<?php

namespace App\Console\Commands;

use App\Services\Media\ImageMigration;
use App\Services\Media\LegacyImageSource;
use App\Services\Media\LegacyImageTargets;
use Illuminate\Bus\Batch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Sleep;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Transitional(legacy-images): remove when media:migrate-images --status shows nothing pending or superseded.
 */
#[Description('Bring existing media up to the current image policy and copy legacy image columns into media')]
#[Signature('media:migrate-images
                            {--status : Report what is left instead of migrating}
                            {--sync : Run in the foreground instead of queueing}
                            {--detach : Queue and return without following the progress}
                            {--cleanup : Delete the files that completed upgrades replaced}
                            {--target= : Only one legacy target, e.g. news.image (skips existing media)}
                            {--limit= : At most this many items per step, newest first}')]
class MigrateImages extends Command
{
    public function handle(ImageMigration $migration): int
    {
        $target = $this->option('target');
        $limit = $this->option('limit');

        if ($target !== null && ! array_key_exists($target, LegacyImageTargets::TARGETS)) {
            $this->error("Unknown target {$target}. Known: ".implode(', ', array_keys(LegacyImageTargets::TARGETS)));

            return self::FAILURE;
        }

        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('--limit must be a positive number.');

            return self::FAILURE;
        }

        $limit = $limit === null ? null : (int) $limit;

        return match (true) {
            (bool) $this->option('status') => $this->status($migration, $target),
            (bool) $this->option('cleanup') => $this->cleanup($migration),
            (bool) $this->option('sync') => $this->runNow($migration, $target, $limit),
            default => $this->queue($migration, $target, $limit),
        };
    }

    private function queue(ImageMigration $migration, ?string $target, ?int $limit): int
    {
        $plan = $migration->plan($target, $limit);
        $this->table(['Step', 'Items', 'Jobs'], [
            ['Existing media', count($plan['upgrade']), (int) ceil(count($plan['upgrade']) / ImageMigration::CHUNK)],
            ...collect($plan['imports'])->map(fn (array $jobs, string $name): array => [
                "Legacy {$name}", array_sum(array_map(fn ($job): int => count($job->ids), $jobs)), count($jobs),
            ])->values()->all(),
        ]);

        $batch = $migration->dispatch($plan);

        if ($batch === null) {
            $this->info('Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->info("Queued {$batch->totalJobs} job(s) on the long-running queue (batch {$batch->id}).");

        if ($this->option('detach')) {
            $this->line('Follow it with: php artisan media:migrate-images --status');

            return self::SUCCESS;
        }

        $this->line('Following the batch; stopping this view (Ctrl+C) does not stop the jobs.');
        $this->follow($batch);

        return self::SUCCESS;
    }

    private function follow(Batch $batch): void
    {
        $bar = $this->output->createProgressBar($batch->totalJobs);
        $bar->start();

        while ($batch = Bus::findBatch($batch->id)) {
            // With allowFailures() a failed job stays "pending"; it has still run.
            $bar->setProgress($batch->totalJobs - $batch->pendingJobs + $batch->failedJobs);

            if ($batch->finished() || $batch->cancelled() || $batch->pendingJobs <= $batch->failedJobs) {
                break;
            }

            Sleep::for(2)->seconds();
        }

        $bar->finish();
        $this->newLine(2);

        if ($batch !== null && $batch->failedJobs > 0) {
            $this->warn("{$batch->failedJobs} job(s) failed; see the log. Running the command again retries what is left.");
        }

        $this->info('Done. Check what is left with: php artisan media:migrate-images --status');
    }

    private function runNow(ImageMigration $migration, ?string $target, ?int $limit): int
    {
        $plan = $migration->plan($target, $limit);

        if ($plan['upgrade'] !== []) {
            $this->line('Existing media');
            $bar = $this->output->createProgressBar(count($plan['upgrade']));
            $bar->start();
            $migration->upgradeNow($plan['upgrade'], $bar->advance(...));
            $bar->finish();
            $this->newLine();
        }

        $migration->legacy()->runNow($target, $limit, $this->rowProgress());
        $this->info('Done. Check what is left with: php artisan media:migrate-images --status');

        return self::SUCCESS;
    }

    private function status(ImageMigration $migration, ?string $target): int
    {
        $legacy = $migration->legacy()->status($target, $this->rowProgress());
        $pendingMedia = $target === null ? $migration->pendingMedia()->count() : 0;
        $pendingImports = 0;

        if ($target === null) {
            $this->table(['Existing media', 'Pending upgrade', 'Superseded files to clean up'], [
                [$migration->ownedMedia()->count(), $pendingMedia, $migration->supersededMedia()->count()],
            ]);
        }

        $this->table(
            ['Legacy target', 'With value', 'With media', 'Pending', 'Missing', 'Placeholder', 'Foreign', 'Junk'],
            collect($legacy)->map(function (array $row, string $name) use (&$pendingImports): array {
                $pendingImports += $row['without_media'][LegacyImageSource::RESOLVED] ?? 0;

                return [
                    $name,
                    $row['with_value'],
                    $row['with_media'],
                    $row['without_media'][LegacyImageSource::RESOLVED] ?? 0,
                    $row['without_media'][LegacyImageSource::MISSING] ?? 0,
                    $row['without_media'][LegacyImageSource::PLACEHOLDER] ?? 0,
                    $row['without_media'][LegacyImageSource::FOREIGN] ?? 0,
                    $row['without_media'][LegacyImageSource::JUNK] ?? 0,
                ];
            })->values()->all(),
        );

        $this->line("{$pendingMedia} media to upgrade, {$pendingImports} legacy rows to import (their file exists but has no media yet).");

        return $pendingMedia === 0 && $pendingImports === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function cleanup(ImageMigration $migration): int
    {
        $query = $migration->supersededMedia();
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();
        $removed = 0;

        $query->lazyById(100)->each(function (Media $media) use ($migration, $bar, &$removed): void {
            $removed += (int) $migration->cleanup($media);
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info("Removed replaced files for {$removed} media.");

        return self::SUCCESS;
    }

    /**
     * A bar per legacy target; with -v, one line per row instead.
     *
     * @return callable(string, int, int, int|string|null, ?string, ?string): void
     */
    private function rowProgress(): callable
    {
        $bar = null;

        return function (string $target, int $checked, int $total, int|string|null $id, ?string $source, ?string $outcome) use (&$bar): void {
            if ($this->output->isVerbose()) {
                $this->line($id === null ? "Checking {$target}: {$total} row(s)." : "{$target} [{$checked}/{$total}] #{$id} {$source}: {$outcome}");

                return;
            }

            if ($id === null) {
                $this->line("Legacy {$target}");
                $bar = $this->output->createProgressBar($total);
                $bar->start();
            } else {
                $bar?->advance();
            }

            if ($bar instanceof ProgressBar && $checked === $total) {
                $bar->finish();
                $this->newLine();
                $bar = null;
            }
        };
    }
}
