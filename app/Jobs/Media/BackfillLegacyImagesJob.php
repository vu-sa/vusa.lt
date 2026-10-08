<?php

namespace App\Jobs\Media;

use App\Listeners\RefreshImageCacheAfterConversion;
use App\Services\Media\LegacyImageImporter;
use App\Services\Media\LegacyImageSource;
use App\Services\Media\LegacyImageTargets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Copies one chunk of legacy images into media. Conversions run inline so a few thousand imports
 * do not flood the default queue, and owners are not touched: the backfill is not an edit.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class BackfillLegacyImagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * @param  list<int|string>  $ids
     */
    public function __construct(public string $target, public array $ids)
    {
        $this->onConnection('long-running');
    }

    public function handle(LegacyImageImporter $importer, ?callable $progress = null): void
    {
        $definition = LegacyImageTargets::TARGETS[$this->target];
        $outcomes = [];

        $queueConversions = config('media-library.queue_conversions_by_default');
        $muted = RefreshImageCacheAfterConversion::$muted;
        config(['media-library.queue_conversions_by_default' => false]);
        RefreshImageCacheAfterConversion::$muted = true;

        try {
            foreach (LegacyImageTargets::rowsWithValue($this->target)->whereKey($this->ids)->get() as $owner) {
                $value = $owner->getAttribute(LegacyImageTargets::urlColumn($this->target));

                try {
                    $outcome = $importer->import($owner, $definition['collection'])['outcome'];
                } catch (Throwable $exception) {
                    $outcome = 'failed';
                    Log::warning('Legacy image import failed', ['target' => $this->target, 'id' => $owner->getKey(), 'error' => $exception->getMessage()]);
                }

                $outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;
                if ($progress !== null) {
                    $progress($owner->getKey(), $value, $outcome);
                }

                if (! in_array($outcome, [LegacyImageSource::RESOLVED, LegacyImageImporter::EXISTING], true)) {
                    Log::info('Legacy image skipped', [
                        'target' => $this->target,
                        'id' => $owner->getKey(),
                        'value' => $owner->getAttribute(LegacyImageTargets::urlColumn($this->target)),
                        'outcome' => $outcome,
                    ]);
                }
            }
        } finally {
            config(['media-library.queue_conversions_by_default' => $queueConversions]);
            RefreshImageCacheAfterConversion::$muted = $muted;
        }

        Log::info('Legacy image chunk imported', ['target' => $this->target, 'outcomes' => $outcomes]);
    }
}
