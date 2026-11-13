<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Documents\DocumentPreviewService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Fetch SharePoint first-page thumbnails for documents that knowledge items link to')]
#[Signature('sharepoint:refresh-thumbnails {--force : Refetch thumbnails even when the file has not changed}')]
class RefreshDocumentThumbnailsCommand extends Command
{
    public function handle(DocumentPreviewService $previews): int
    {
        $documents = Document::query()->whereHas('knowledgeItemSources')->get()
            ->filter(fn (Document $document): bool => $this->option('force') || $document->eTag === null || $previews->needsThumbnail($document));

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        foreach ($documents as $document) {
            if ($this->syncedOnce($document)) {
                $previews->refreshThumbnail($document);
            }
            $this->line(" {$document->id}: ".($document->thumbnail_path ?? 'no thumbnail'), verbosity: 'v');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $withThumbnail = $documents->filter(fn (Document $document): bool => $document->thumbnail_path !== null)->count();
        $this->info("Checked {$documents->count()} documents, {$withThumbnail} now have a thumbnail.");

        return self::SUCCESS;
    }

    /**
     * Hidden documents are not synced on a schedule, so one may still lack the eTag its thumbnail is keyed by.
     */
    private function syncedOnce(Document $document): bool
    {
        if ($document->eTag !== null) {
            return true;
        }

        try {
            return $document->refreshFromSharepoint()?->eTag !== null;
        } catch (\Throwable $e) {
            $this->line(" {$document->id}: sync failed – {$e->getMessage()}", verbosity: 'v');

            return false;
        }
    }
}
