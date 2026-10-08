<?php

namespace App\Listeners;

use App\Contracts\ImageMediaOwner;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/**
 * Cached image URLs point at the original until the queued conversion they name exists; this
 * swaps the conversion in.
 */
class RefreshImageCacheAfterConversion
{
    /** The backfill refreshes caches itself and reindexes once at the end. */
    public static bool $muted = false;

    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $owner = $event->media->model;

        if (self::$muted || ! $owner instanceof ImageMediaOwner) {
            return;
        }

        $collection = $event->media->collection_name;
        $columns = $owner->imageCacheColumns()[$collection] ?? null;

        if ($columns === null || $columns['conversion'] !== $event->conversion->getName()) {
            return;
        }

        if ($owner->refreshImageCache($collection)) {
            $owner->afterImageCacheRefreshed($collection);
        }
    }
}
