<?php

namespace App\Console\Commands;

use App\Services\Documents\SharepointDocumentDiscovery;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('List every SharePoint archive file in Dokumentai: new files wait as pending, deleted ones are marked removed')]
#[Signature('sharepoint:discover-documents
                            {--full : Ignore the stored delta link and list the whole archive}
                            {--dry-run : Report what would change without writing anything}
                            {--allow-mass-removal : Apply a run that would mark many documents as removed}
                            {--limit= : Add at most this many new files in folder order; repeat to add the archive in batches (removes nothing, saves no feed position)}')]
class DiscoverSharepointDocumentsCommand extends Command
{
    public function handle(SharepointDocumentDiscovery $discovery): int
    {
        $result = $discovery->run(
            full: (bool) $this->option('full'),
            dryRun: (bool) $this->option('dry-run'),
            allowMassRemoval: (bool) $this->option('allow-mass-removal'),
            limit: $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null,
        );

        if ($result->abortReason === 'already_running') {
            $this->warn('Another discovery run is in progress.');

            return self::FAILURE;
        }

        if ($result->abortReason === 'throttled') {
            $this->warn('SharePoint asked to pause; discovery resumes from the same place once the pause has passed.');

            return self::FAILURE;
        }

        if ($result->abortReason === 'mass_removal') {
            $this->error("Refused: this run would mark {$result->removed} documents as removed. Check the SharePoint drive and permissions, then rerun with --allow-mass-removal if it is right.");

            return self::FAILURE;
        }

        $this->table(['', 'Count'], [
            [($result->dryRun ? 'Would create' : 'Created').($result->baseline ? ' (hidden, first run)' : ' (pending)'), $result->created],
            [$result->dryRun ? 'Would update' : 'Updated', $result->updated],
            [$result->dryRun ? 'Would mark removed' : 'Marked removed', $result->removed],
            ['Restored', $result->restored],
            ['Failed (retried next run)', $result->failed - $result->givenUp],
            ['Given up after repeated failures (retried weekly)', $result->givenUp],
        ]);

        if ($result->unmatchedLabels !== []) {
            $this->warn('Padalinys labels with no matching institution: '.implode(', ', array_keys($result->unmatchedLabels)));
        }

        $this->info($result->full ? 'Full listing.' : 'Changes since the last run.');

        if ($result->limited) {
            $this->info('Limited run: nothing was marked removed. Run again to add the next batch; a run without --limit finishes the job.');
        }

        return $result->failed > $result->givenUp ? self::FAILURE : self::SUCCESS;
    }
}
