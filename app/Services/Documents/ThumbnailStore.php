<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ThumbnailStore
{
    public function put(string $path, string $imageBinary): void
    {
        $image = Image::decodeBinary($imageBinary)->scaleDown(width: 600)->encodeUsingFileExtension('webp', quality: 80);
        if (! Storage::put($path, $image->toString())) {
            throw new \RuntimeException('Could not store thumbnail');
        }
    }
}
