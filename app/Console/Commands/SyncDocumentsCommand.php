<?php

namespace App\Console\Commands;

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Jobs\SyncStaleDocumentsJob;
use App\Models\Document;
use Illuminate\Console\Command;

class SyncDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharepoint:sync-documents 
                            {--all : Sync all documents regardless of staleness}
                            {--failed : Only sync documents with failed status}
                            {--limit=50 : Maximum number of documents to sync}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync documents from SharePoint with various options';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('SharePoint Document Sync Command');
        $this->info('================================');

        if ($this->option('all')) {
            return $this->syncAllDocuments();
        }

        if ($this->option('failed')) {
            return $this->syncFailedDocuments();
        }

        // Default: sync stale documents
        return $this->syncStaleDocuments();
    }

    private function syncStaleDocuments()
    {
        $this->info('Dispatching stale documents sync job...');

        if ($this->option('dry-run')) {
            $staleCount = Document::query()
                ->where(function ($query) {
                    $query->whereNull('checked_at')
                        ->orWhere('checked_at', '<', now()->subDay());
                })
                ->where('sync_status', '!=', 'syncing')
                ->count();

            $this->info("Would sync {$staleCount} stale documents");

            return 0;
        }

        SyncStaleDocumentsJob::dispatch();
        $this->info('Stale documents sync job dispatched successfully');

        return 0;
    }

    private function syncAllDocuments()
    {
        $limit = (int) $this->option('limit');

        $query = Document::query()
            ->where('sync_status', '!=', 'syncing')
            ->orderBy('checked_at', 'asc')
            ->limit($limit);

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("Would sync {$count} documents (limit: {$limit})");

            $documents = $query->get(['id', 'title', 'checked_at', 'sync_status']);
            $this->table(
                ['ID', 'Title', 'Last Checked', 'Status'],
                $documents->map(fn ($doc) => [
                    $doc->id,
                    substr($doc->title ?? 'Untitled', 0, 50),
                    $doc->checked_at ? $doc->checked_at->format('Y-m-d H:i') : 'Never',
                    $doc->sync_status ?? 'pending',
                ])
            );

            return 0;
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->info('No documents to sync');

            return 0;
        }

        $this->info("Dispatching sync jobs for {$documents->count()} documents...");

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        foreach ($documents as $document) {
            SyncDocumentFromSharePointJob::dispatch($document);
            $bar->advance();
            usleep(100000); // 100ms delay between dispatches
        }

        $bar->finish();
        $this->newLine();
        $this->info('All sync jobs dispatched successfully');

        return 0;
    }

    private function syncFailedDocuments()
    {
        $limit = (int) $this->option('limit');

        $query = Document::query()
            ->where('sync_status', 'failed')
            ->where('sync_attempts', '<', 5) // Don't retry documents that have failed too many times
            ->orderBy('last_sync_attempt_at', 'asc')
            ->limit($limit);

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("Would retry {$count} failed documents (limit: {$limit})");

            $documents = $query->get(['id', 'title', 'sync_attempts', 'sync_error_message']);
            $this->table(
                ['ID', 'Title', 'Attempts', 'Last Error'],
                $documents->map(fn ($doc) => [
                    $doc->id,
                    substr($doc->title ?? 'Untitled', 0, 30),
                    $doc->sync_attempts,
                    substr($doc->sync_error_message ?? '', 0, 50),
                ])
            );

            return 0;
        }

        $failedDocuments = $query->get();

        if ($failedDocuments->isEmpty()) {
            $this->info('No failed documents to retry');

            return 0;
        }

        $this->info("Retrying sync for {$failedDocuments->count()} failed documents...");

        foreach ($failedDocuments as $document) {
            SyncDocumentFromSharePointJob::dispatch($document);
            $this->line("Dispatched retry for: {$document->title}");
            usleep(200000); // 200ms delay for retries
        }

        $this->info('All retry jobs dispatched successfully');

        return 0;
    }
}
