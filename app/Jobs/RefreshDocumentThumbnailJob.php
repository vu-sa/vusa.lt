<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Documents\DocumentPreviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshDocumentThumbnailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waiting for the document's lock releases the job, which counts as an attempt.
     */
    public $tries = 10;

    public $timeout = 60;

    public bool $deleteWhenMissingModels = true;

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

    public function handle(DocumentPreviewService $previews): void
    {
        if ($this->force || $previews->needsThumbnail($this->document)) {
            $previews->refreshThumbnail($this->document);
        }
    }
}
