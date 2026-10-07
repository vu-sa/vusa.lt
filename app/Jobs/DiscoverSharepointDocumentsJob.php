<?php

namespace App\Jobs;

use App\Services\Documents\SharepointDocumentDiscovery;
use App\Settings\DocumentDiscoverySettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled every 15 minutes and queued by "Tikrinti SharePoint" in Dokumentai. Runs on the
 * long-running connection: a scan can take far longer than the default queue's retry_after.
 */
class DiscoverSharepointDocumentsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** The first full listing of the archive fetches metadata for every file. */
    public int $timeout = 1800;

    public int $uniqueFor = 1800;

    /** A full listing catches deletions older than the change feed's memory. */
    private const FULL_LISTING_EVERY_DAYS = 7;

    public function __construct(public bool $full = false)
    {
        $this->onConnection('long-running');
    }

    public function handle(SharepointDocumentDiscovery $discovery, DocumentDiscoverySettings $settings): void
    {
        // Decided here rather than by a second schedule, which the unique lock would silently drop.
        $lastFull = $settings->last_full_run_at;
        $full = $this->full || ($lastFull !== null && Carbon::parse($lastFull)->lt(now()->subDays(self::FULL_LISTING_EVERY_DAYS)));

        $result = $discovery->run(full: $full);

        if ($result->wasAborted() || $result->failed > 0) {
            Log::warning('SharePoint document discovery did not finish cleanly', $result->toArray());
        }
    }
}
