<?php

namespace App\Jobs\Media;

use App\Services\Media\SupportEvidenceTransfer;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Transitional(private-evidence): remove when media:privatize-evidence --status reports no pending files or public copies. */
class PrivatizeSupportEvidence implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function __construct(public int $mediaId, public bool $cleanup = false)
    {
        $this->onConnection('long-running');
    }

    public function handle(SupportEvidenceTransfer $transfer): void
    {
        if (! $this->batch()?->cancelled()) {
            $transfer->transfer($this->mediaId, $this->cleanup);
        }
    }
}
