<?php

namespace App\Jobs\Media;

use App\Actions\Media\AddImageMedia;
use App\Contracts\ImageMediaOwner;
use App\Listeners\RefreshImageCacheAfterConversion;
use App\Models\Calendar;
use App\Services\ImageUploadService;
use App\Services\Media\LegacyImageResolver;
use App\Support\Media\ImageConversions;
use App\Support\StagingProtection;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UpgradeImageMedia implements ShouldQueue
{
    use Batchable, Queueable;

    public int $timeout = 1800;

    public function __construct(public array $ids)
    {
        $this->onConnection('long-running');
    }

    public static function conversionsComplete(Media $media): bool
    {
        return collect(array_keys(ImageConversions::WIDTHS))->every($media->hasGeneratedConversion(...));
    }

    public static function isCurrent(Media $media): bool
    {
        return (int) $media->getCustomProperty('image_policy') >= ImageConversions::VERSION && self::conversionsComplete($media);
    }

    private static function trashedOwner(Media $media): ?Model
    {
        $class = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

        return class_exists($class) && in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()->find($media->model_id)
            : null;
    }

    public function handle(ImageUploadService $images, FileManipulator $files, LegacyImageResolver $resolver): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $queued = config('media-library.queue_conversions_by_default');
        $muted = RefreshImageCacheAfterConversion::$muted;
        config(['media-library.queue_conversions_by_default' => false]);
        RefreshImageCacheAfterConversion::$muted = true;
        try {
            foreach (Media::query()->whereKey($this->ids)->with('model')->get() as $media) {
                $this->upgrade($media, $images, $files, $resolver);
            }
        } finally {
            config(['media-library.queue_conversions_by_default' => $queued]);
            RefreshImageCacheAfterConversion::$muted = $muted;
        }
    }

    private function upgrade(Media $media, ImageUploadService $images, FileManipulator $files, LegacyImageResolver $resolver): void
    {
        $owner = $media->model ?? self::trashedOwner($media);
        $readOnly = ! StagingProtection::diskIsWritable($media->disk) || ! StagingProtection::diskIsWritable($media->conversions_disk);
        if (! $owner instanceof ImageMediaOwner || ! in_array($media->mime_type, ImageConversions::MIME_TYPES, true)
            || ($readOnly && ! app()->environment('staging')) || (! $readOnly && self::isCurrent($media))) {
            return;
        }

        $before = $media->getAttributes();
        try {
            self::moveCalendarFocalPoint($media, $owner);
            if ($readOnly || (int) $media->getCustomProperty('image_policy') < ImageConversions::VERSION) {
                $this->replaceMaster($media, $readOnly, $images, $resolver);
            }
            $files->createDerivedFiles($media, array_keys(ImageConversions::WIDTHS));
            $media->refresh();
            $media->setCustomProperty('image_policy', ImageConversions::VERSION);
            if (! self::isCurrent($media)) {
                throw new \RuntimeException('Image conversions are incomplete');
            }
            $media->save();
            $owner->refreshImageCache($media->collection_name);
        } catch (\Throwable $exception) {
            $media->setRawAttributes($before)->saveQuietly();
            report($exception);
        }
    }

    /** Re-encodes the best available source beside the old master, which stays until cleanup. */
    private function replaceMaster(Media $media, bool $readOnly, ImageUploadService $images, LegacyImageResolver $resolver): void
    {
        $source = $media->getPath();
        $legacy = $resolver->resolve($media->getCustomProperty('legacy_source'));
        $currentSize = @getimagesize($source);
        $legacySize = $legacy->isResolved() ? @getimagesize($legacy->path) : false;
        if ($legacySize !== false && ($currentSize === false || $legacySize[0] * $legacySize[1] >= $currentSize[0] * $currentSize[1])) {
            $source = $legacy->path;
        }

        $contents = (string) file_get_contents($source);
        $lossless = ImageConversions::isArtwork($contents, $media->collection_name);
        $normalized = $images->normalize($contents, lossless: $lossless);
        $name = AddImageMedia::fileName(Str::substr(pathinfo($media->file_name, PATHINFO_FILENAME), 0, 54).'-v'.ImageConversions::VERSION);

        if ($readOnly) {
            // Staging reads production's disk: the upgrade goes to its own disk instead.
            $media->disk = config('media-library.disk_name');
            $media->conversions_disk = config('media-library.disk_name');
            StagingProtection::ensureDiskIsWritable($media->disk);
        } else {
            $media->setCustomProperty('superseded_file', $media->file_name);
        }

        $directory = dirname($media->getPathRelativeToRoot());
        throw_unless(Storage::disk($media->disk)->put($directory.'/'.$name, $normalized['contents']), new \RuntimeException('Cannot save upgraded master'));
        $media->file_name = $name;
        $media->mime_type = 'image/webp';
        $media->size = strlen($normalized['contents']);
        $media->setCustomProperty('width', $normalized['width']);
        $media->setCustomProperty('height', $normalized['height']);
        $media->setCustomProperty('lossless', $lossless);
        $media->generated_conversions = [];
        // Only the shared conversions are rendered; cleanup deletes the old responsive files.
        $media->responsive_images = [];
        $media->saveQuietly();
    }

    /** Calendar kept its main image's focal point on the event; it now travels with the image. */
    private static function moveCalendarFocalPoint(Media $media, ImageMediaOwner $owner): void
    {
        if (! $owner instanceof Calendar || $media->collection_name !== 'main_image' || $media->hasCustomProperty('focal_point')) {
            return;
        }

        $focalPoint = $owner->getRawOriginal('main_image_focal_point');

        if (is_string($focalPoint) && $focalPoint !== '') {
            $media->setCustomProperty('focal_point', $focalPoint);
        }
    }
}
