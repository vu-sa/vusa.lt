<?php

namespace App\Actions\Media;

use App\Services\ImageUploadService;
use App\Support\Media\ImageConversions;
use App\Support\StagingProtection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Every image enters the media library the same way: normalized to a bounded WebP original with
 * its dimensions recorded, which srcset and layout reservation rely on.
 */
class AddImageMedia
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function execute(HasMedia $owner, UploadedFile|string $source, string $collection, ?string $name = null, array $properties = []): Media
    {
        StagingProtection::ensureDiskIsWritable(config('media-library.disk_name'));

        $name ??= $source instanceof UploadedFile ? pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME) : 'image';
        $bytes = $source instanceof UploadedFile ? (string) file_get_contents($source->getRealPath()) : $source;
        $lossless = ($properties['lossless'] ?? false) || ImageConversions::isArtwork($bytes, $collection);
        $normalized = $this->images->normalize($bytes, lossless: $lossless);

        return $owner->addMediaFromString($normalized['contents'])
            ->usingName($name)
            ->usingFileName(self::fileName($name))
            ->withCustomProperties([
                ...$properties,
                'lossless' => $lossless,
                'image_policy' => ImageConversions::VERSION,
                'width' => $normalized['width'],
                'height' => $normalized['height'],
            ])
            ->toMediaCollection($collection);
    }

    /**
     * Short enough that a conversion's URL fits the 125-character columns that cache it.
     */
    public static function fileName(string $name): string
    {
        return (rtrim(Str::substr(Str::slug($name), 0, 60), '-') ?: 'image').'.webp';
    }
}
