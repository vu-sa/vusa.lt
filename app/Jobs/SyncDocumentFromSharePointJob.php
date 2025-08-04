<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDocumentFromSharePointJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document
    ) {
        // Set queue name for better organization
        $this->queue = 'sharepoint-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SharePoint sync for document', [
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Call the model's sync method which now has proper error handling
            $result = $this->document->refreshFromSharepoint();

            if ($result === null) {
                Log::info('SharePoint document was already up to date', [
                    'document_id' => $this->document->id,
                ]);
            } else {
                Log::info('SharePoint document synced successfully', [
                    'document_id' => $this->document->id,
                    'title' => $result->title,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SharePoint sync job failed', [
                'document_id' => $this->document->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // If this was the last attempt, mark as failed in a different way
            if ($this->attempts() >= $this->tries) {
                Log::error('SharePoint sync job exhausted all retries', [
                    'document_id' => $this->document->id,
                    'total_attempts' => $this->tries,
                ]);
            }

            // Let the queue system handle retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SharePoint sync job failed permanently', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);

        // Update document status to indicate permanent failure
        $this->document->update([
            'sync_status' => 'failed',
            'sync_error_message' => 'Job failed after '.$this->tries.' attempts: '.$exception->getMessage(),
        ]);
    }
}
