<?php

namespace App\Console\Commands;

use App\Services\Media\LegacyFileReport;
use App\Support\StagingProtection;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

/**
 * Transitional(legacy-images): remove once the legacy folders have been cleaned up.
 */
#[Description('Report legacy image files nothing references any more, and optionally delete them')]
#[Signature('media:legacy-files
                            {--group= : Only one folder, e.g. contacts, or "files" for copied file manager files}
                            {--list : Print every unreferenced path}
                            {--delete : Delete the unreferenced files (refused on staging and before the legacy columns are dropped)}
                            {--force : Delete without asking}')]
class MediaLegacyFiles extends Command
{
    public function handle(LegacyFileReport $report): int
    {
        if ($this->option('delete')) {
            StagingProtection::ensureFilesAreWritable();

            $blockers = $report->deletionBlockers();

            if ($blockers !== []) {
                $this->error('Refused: deleting is not safe yet.');
                array_map(fn (string $blocker) => $this->line("  - {$blocker}"), $blockers);

                return self::FAILURE;
            }
        }

        $group = $this->option('group');
        $progress = $this->output->isVerbose() ? function (string $folder, int $checked, int $total, ?string $path, ?bool $referenced): void {
            $this->line($path === null
                ? "Scanning {$folder}..."
                : "{$folder} [{$checked}/{$total}] {$path}: ".($referenced ? 'referenced' : 'unreferenced'));
        } : null;
        $result = $report->build(is_string($group) ? $group : null, $progress);

        $this->table(
            ['Folder', 'Files', 'Unreferenced', 'Reclaimable'],
            collect($result)->map(fn (array $row, string $name): array => [$name, $row['files'], count($row['unreferenced']), Number::fileSize($row['bytes'])])->values()->all(),
        );

        $unreferenced = collect($result)->flatMap(fn (array $row): array => $row['unreferenced']);

        if ($this->option('list')) {
            $unreferenced->each(fn (string $path) => $this->line($path));
        }

        if (! $this->option('delete') || $unreferenced->isEmpty()) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$unreferenced->count()} unreferenced file(s)?")) {
            return self::SUCCESS;
        }

        $deleted = $unreferenced->filter(fn (string $path): bool => $report->delete($path));
        Log::info('Deleted legacy image files', ['count' => $deleted->count(), 'paths' => $deleted->values()->all()]);
        $this->info("Deleted {$deleted->count()} file(s).");

        return self::SUCCESS;
    }
}
