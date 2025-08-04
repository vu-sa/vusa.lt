<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStaleDocumentsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes for bulk operation

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue name for better organization
        $this->queue = 'sharepoint-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting scheduled sync of stale documents');

        // Get documents that need syncing
        $staleDocuments = $this->getStaleDocuments();

        if ($staleDocuments->isEmpty()) {
            Log::info('No stale documents found for sync');
            return;
        }

        Log::info('Found stale documents for sync', [
            'count' => $staleDocuments->count(),
        ]);

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        // Process documents in batches to avoid overwhelming SharePoint API
        $staleDocuments->chunk(10)->each(function ($batch) use (&$successCount, &$failedCount, &$skippedCount) {
            foreach ($batch as $document) {
                // Skip documents that have failed too many times recently
                if ($this->shouldSkipDocument($document)) {
                    $skippedCount++;
                    Log::debug('Skipping document due to recent failures', [
                        'document_id' => $document->id,
                        'sync_attempts' => $document->sync_attempts,
                    ]);
                    continue;
                }

                // Dispatch individual sync job
                SyncDocumentFromSharePointJob::dispatch($document);
                $successCount++;

                // Small delay to be respectful to SharePoint API
                usleep(250000); // 250ms delay between dispatches
            }

            // Longer delay between batches
            if (!$batch->isEmpty()) {
                sleep(2); // 2 second delay between batches
            }
        });

        Log::info('Completed scheduled sync dispatch', [
            'dispatched' => $successCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'total_found' => $staleDocuments->count(),
        ]);
    }

    /**
     * Get documents that need syncing based on age and status
     */
    private function getStaleDocuments()
    {
        return Document::query()
            // Documents not checked in the last 24 hours
            ->where(function ($query) {
                $query->whereNull('checked_at')
                    ->orWhere('checked_at', '<', now()->subDay());
            })
            // Exclude documents that are currently being synced
            ->where('sync_status', '!=', 'syncing')
            // Prioritize active documents and documents with anonymous URLs (public)
            ->orderByRaw("
                CASE 
                    WHEN is_active = 1 AND anonymous_url IS NOT NULL THEN 1
                    WHEN is_active = 1 THEN 2
                    WHEN anonymous_url IS NOT NULL THEN 3
                    ELSE 4
                END
            ")
            // Secondary sort by last check time (oldest first)
            ->orderBy('checked_at', 'asc')
            // Limit to prevent overwhelming the system
            ->limit(100)
            ->get();
    }

    /**
     * Determine if a document should be skipped due to recent failures
     */
    private function shouldSkipDocument(Document $document): bool
    {
        // Skip if it has failed more than 5 times
        if ($document->sync_attempts > 5) {
            return true;
        }

        // Skip if it failed recently and has multiple attempts
        if ($document->sync_status === 'failed' && 
            $document->sync_attempts >= 3 && 
            $document->last_sync_attempt_at && 
            $document->last_sync_attempt_at > now()->subHours(6)) {
            return true;
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Scheduled document sync job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
