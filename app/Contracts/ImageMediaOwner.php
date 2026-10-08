<?php

namespace App\Contracts;

use Spatie\MediaLibrary\HasMedia;

/**
 * A model whose images live in shared-conversion collections. Implemented through
 * App\Models\Traits\HasImageMedia.
 *
 * @phpstan-import-type ImageDataArray from \App\Support\Media\ImageData
 */
interface ImageMediaOwner extends HasMedia
{
    /**
     * @return ImageDataArray|null
     */
    public function imageData(string $collection): ?array;

    public function afterImageMediaChanged(string $collection): void;

    public function afterImageCacheRefreshed(string $collection): void;

    /**
     * @return array<string, array{url: string, conversion: string, focal?: string, author?: string}>
     */
    public function imageCacheColumns(): array;

    public function refreshImageCache(string $collection): bool;

    /**
     * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
     *
     * @return array<string, array{url: string, focal?: string, author?: string}>
     */
    public function legacyImageColumns(): array;
}
