<?php

namespace App\Jobs\Media;

use App\Models\Calendar;
use App\Support\Media\ImageConversions;
use App\Support\StagingProtection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;
use Throwable;

/**
 * Brings media that predates the shared conversions in line: records dimensions, moves the
 * calendar focal point onto its image, rebuilds thumb/medium/large and deletes the responsive
 * images and old conversions nothing renders. Rebuilding is skipped where the disk is read-only
 * (staging reading production's media).
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class AlignExistingMediaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * @param  list<int>  $mediaIds
     */
    public function __construct(public array $mediaIds)
    {
        $this->onConnection('long-running');
    }

    public function handle(FileManipulator $fileManipulator): void
    {
        $queueConversions = config('media-library.queue_conversions_by_default');
        config(['media-library.queue_conversions_by_default' => false]);

        try {
            Media::query()->whereKey($this->mediaIds)->with('model')->get()->each(function (Media $media) use ($fileManipulator): void {
                $writable = StagingProtection::diskIsWritable($media->disk) && StagingProtection::diskIsWritable($media->conversions_disk);

                $this->moveCalendarFocalPoint($media);
                $this->recordDimensions($media);

                if ($media->isDirty()) {
                    $media->save();
                }

                if ($writable) {
                    $this->rebuildDerivedFiles($media, $fileManipulator);
                }
            });
        } finally {
            config(['media-library.queue_conversions_by_default' => $queueConversions]);
        }
    }

    private function moveCalendarFocalPoint(Media $media): void
    {
        $owner = $media->model;

        if (! $owner instanceof Calendar || $media->collection_name !== 'main_image' || $media->hasCustomProperty('focal_point')) {
            return;
        }

        $focalPoint = $owner->getRawOriginal('main_image_focal_point');

        if (is_string($focalPoint) && $focalPoint !== '') {
            $media->setCustomProperty('focal_point', $focalPoint);
        }
    }

    private function recordDimensions(Media $media): void
    {
        if ($media->hasCustomProperty('width')) {
            return;
        }

        $size = @getimagesize($media->getPath());

        if ($size !== false) {
            $media->setCustomProperty('width', $size[0]);
            $media->setCustomProperty('height', $size[1]);
        }
    }

    private function rebuildDerivedFiles(Media $media, FileManipulator $fileManipulator): void
    {
        try {
            $fileManipulator->createDerivedFiles($media, array_keys(ImageConversions::WIDTHS));
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $media->refresh();

        $disk = Storage::disk($media->conversions_disk);
        $pathGenerator = PathGeneratorFactory::create($media);
        $current = ConversionCollection::createForMedia($media)->getConversionsFiles($media->collection_name);

        foreach ($disk->files($pathGenerator->getPathForConversions($media)) as $file) {
            if (! $current->contains(basename($file))) {
                $disk->delete($file);
            }
        }

        if ($media->responsive_images !== []) {
            $disk->deleteDirectory($pathGenerator->getPathForResponsiveImages($media));
            $media->responsive_images = [];
        }

        $media->generated_conversions = array_intersect_key(
            $media->generated_conversions,
            ImageConversions::WIDTHS,
        );
        $media->save();
    }
}
