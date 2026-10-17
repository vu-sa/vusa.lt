<?php

namespace App\Listeners;

use App\Contracts\ImageMediaOwner;
use App\Jobs\Media\UpgradeImageMedia;
use App\Services\ContentResolution\ContentPartResolver;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/**
 * Cached image URLs point at the original until the queued conversions exist; once the last one
 * finishes this swaps them in and republishes the owner, whose srcset changed even when the URL did not.
 */
class RefreshImageCacheAfterConversion
{
    /** The upgrade job refreshes caches itself and reindexes its owners. */
    public static bool $muted = false;

    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $owner = $event->media->model;

        if (self::$muted || ! $owner instanceof ImageMediaOwner || ! UpgradeImageMedia::conversionsComplete($event->media)) {
            return;
        }

        $collection = $event->media->collection_name;
        $owner->refreshImageCache($collection);
        $owner->afterImageMediaChanged($collection);
        Cache::tags([ContentPartResolver::CACHE_TAG])->flush();
    }
}
