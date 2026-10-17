<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Scaled-down WebP copies of stored images, generated once per source version. Shared by the file
 * manager's thumbnails and the public `/img` route.
 */
class ResizedImageCache
{
    /** Absolute path of the derivative; never wider than the source. */
    public function path(string $storagePath, int $width, int $quality): string
    {
        // The source's mtime is part of the key, so a replaced original gets a fresh derivative.
        $cacheKey = hash('xxh128', $storagePath.'|'.Storage::lastModified($storagePath).'|'.$width.'|'.$quality);
        $cachePath = Storage::path('thumbnails/'.$cacheKey.'.webp');

        if (! is_file($cachePath)) {
            Storage::makeDirectory('thumbnails');

            Image::decodePath(Storage::path($storagePath))
                ->scaleDown(width: $width)
                ->encodeUsingFileExtension('webp', quality: $quality)
                ->save($cachePath);
        }

        return $cachePath;
    }
}
