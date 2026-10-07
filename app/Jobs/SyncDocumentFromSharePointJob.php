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
     * Waiting for the document's lock releases the job, which counts as an attempt; `$maxExceptions` caps real failures.
     */
    public $tries = 15;

    public int $maxExceptions = 3;

    /**
     * Below the default connections' retry_after (90 s), or a second worker would start the same sync.
     */
    public $timeout = 80;

    /**
     * Delete this job silently instead of failing when the Document was deleted before this
     * job (or a retry) ran — deserializing a since-deleted model otherwise throws
     * ModelNotFoundException.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document,
        public bool $force = false,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [DocumentSharepointLock::for($this->document->getKey())];
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
            $result = $this->document->refreshFromSharepoint($this->force);

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
        } catch (\Throwable $e) {
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
            'attempts' => $this->attempts(),
        ]);

        // Not through the job's model: a failed pass may have left a refused link on it.
        Document::query()->whereKey($this->document->getKey())->update([
            'sync_status' => 'failed',
            'sync_error_message' => 'Job failed after '.$this->attempts().' attempts: '.$exception->getMessage(),
        ]);
    }
}
