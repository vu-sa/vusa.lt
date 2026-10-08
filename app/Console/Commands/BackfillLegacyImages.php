<?php

namespace App\Console\Commands;

use App\Services\Media\LegacyImageBackfill;
use App\Services\Media\LegacyImageSource;
use App\Services\Media\LegacyImageTargets;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
#[Description('Copy legacy image columns into media, or report how far the backfill has got')]
#[Signature('media:backfill-legacy-images
                            {--status : Report progress per target instead of importing}
                            {--target= : Limit to one target, e.g. news.image}
                            {--sync : Import now instead of queueing}
                            {--limit= : With --sync, import at most this many rows per target}')]
class BackfillLegacyImages extends Command
{
    public function handle(LegacyImageBackfill $backfill): int
    {
        $target = $this->option('target');

        if ($target !== null && ! array_key_exists($target, LegacyImageTargets::TARGETS)) {
            $this->error("Unknown target {$target}. Known: ".implode(', ', array_keys(LegacyImageTargets::TARGETS)));

            return self::FAILURE;
        }

        if ($this->option('status')) {
            return $this->reportStatus($backfill, $target);
        }

        if ($this->option('sync')) {
            $limit = $this->option('limit');
            $backfill->runNow($target, is_numeric($limit) ? (int) $limit : null, $this->progress());
            $this->info('Imported. Run with --status to see what is left.');

            return self::SUCCESS;
        }

        $progress = $this->output->isVerbose() ? function (string $name, int $rows, int $chunks): void {
            $this->line("{$name}: {$rows} row(s), {$chunks} import chunk(s).");
        } : null;
        $this->info('Queued '.$backfill->dispatch($target, $progress).' import chunk(s) on the long-running queue.');

        return self::SUCCESS;
    }

    private function reportStatus(LegacyImageBackfill $backfill, ?string $target): int
    {
        $status = $backfill->status($target, $this->progress());
        $pending = 0;

        $this->table(
            ['Target', 'With value', 'With media', 'Pending', 'Missing', 'Placeholder', 'Foreign', 'Junk'],
            collect($status)->map(function (array $row, string $name) use (&$pending): array {
                $pending += $row['without_media'][LegacyImageSource::RESOLVED] ?? 0;

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

        $this->line("{$pending} pending: rows whose file exists but has not been imported yet.");

        return self::SUCCESS;
    }

    private function progress(): ?callable
    {
        return $this->output->isVerbose() ? function (string $target, int $checked, int $total, int|string|null $id, ?string $source, ?string $outcome): void {
            if ($id === null) {
                $this->line("Checking {$target}: {$total} row(s).");
            } else {
                $this->line("{$target} [{$checked}/{$total}] #{$id} {$source}: {$outcome}");
            }
        } : null;
    }
}
