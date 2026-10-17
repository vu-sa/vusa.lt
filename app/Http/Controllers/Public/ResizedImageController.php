<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Media\ResizedImageCache;
use App\Support\Media\ImageConversions;
use App\Support\Media\ResizableImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResizedImageController extends Controller
{
    public function __invoke(string $width, string $path, ResizedImageCache $cache): BinaryFileResponse
    {
        $source = ResizableImage::storagePath($path);

        abort_if($source === null, 404);

        $dimensions = @getimagesize(Storage::path($source));
        abort_if($dimensions === false, 404);
        if ((int) $width >= $dimensions[0]) {
            return response()->file(Storage::path($source), ['Content-Type' => $dimensions['mime']])
                ->setPublic()->setMaxAge(604800);
        }

        // The header bytes are enough to tell a transparent logo from a photo.
        $head = (string) file_get_contents(Storage::path($source), length: 65536);
        $quality = ImageConversions::isArtwork($head) ? 100 : ImageConversions::CONVERSION_QUALITY;

        return response()
            ->file($cache->path($source, (int) $width, $quality), ['Content-Type' => 'image/webp'])
            ->setPublic()
            ->setMaxAge(604800);
    }
}
