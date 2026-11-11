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
            ->filter(fn (Document $document): bool => $this->option('force') || $previews->needsThumbnail($document));

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        foreach ($documents as $document) {
            $previews->refreshThumbnail($document);
            $this->line(" {$document->id}: ".($document->thumbnail_path ?? 'no thumbnail'), verbosity: 'v');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $withThumbnail = $documents->filter(fn (Document $document): bool => $document->thumbnail_path !== null)->count();
        $this->info("Checked {$documents->count()} documents, {$withThumbnail} now have a thumbnail.");

        return self::SUCCESS;
    }
}
