<?php

namespace App\Actions\Media;

use App\Services\ImageUploadService;
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
        $normalized = $this->images->normalize($source);

        return $owner->addMediaFromString($normalized['contents'])
            ->usingName($name)
            ->usingFileName(self::fileName($name))
            ->withCustomProperties([
                ...$properties,
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
