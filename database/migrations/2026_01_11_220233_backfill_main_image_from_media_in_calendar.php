<?php

use App\Models\Calendar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfill main_image from first media item for existing calendar events.
     * Uses direct database copying to avoid expensive image re-processing.
     *
     * PERFORMANCE NOTE: The original approach used addMediaFromUrl() which:
     * 1. Downloads the image from URL (network I/O)
     * 2. Generates responsive images (very CPU intensive - multiple sizes)
     * 3. Runs WebP conversion synchronously
     * This could take 10-30+ seconds PER IMAGE on a 3 vCPU server.
     *
     * New approach: Copy existing media records directly in the database,
     * then copy the physical files. No image re-processing needed.
     */
    public function up(): void
    {
        // Get IDs of calendars that already have main_image media (to exclude)
        $calendarsWithMainImage = DB::table('media')
            ->where('model_type', 'App\\Models\\Calendar')
            ->where('collection_name', 'main_image')
            ->pluck('model_id')
            ->toArray();

        // Process calendars that have gallery images but no main_image media
        // Use chunking to avoid memory issues
        Media::where('model_type', 'App\\Models\\Calendar')
            ->where('collection_name', 'images')
            ->whereNotIn('model_id', $calendarsWithMainImage)
            ->orderBy('model_id')
            ->orderBy('order_column') // Get first image per calendar
            ->chunkById(100, function ($mediaItems) use (&$calendarsWithMainImage) {
                $processedCalendarIds = [];

                foreach ($mediaItems as $sourceMedia) {
                    // Skip if we already processed this calendar in this chunk
                    if (in_array($sourceMedia->model_id, $processedCalendarIds)) {
                        continue;
                    }

                    // Skip if calendar already has main_image (from previous chunk)
                    if (in_array($sourceMedia->model_id, $calendarsWithMainImage)) {
                        continue;
                    }

                    try {
                        // Copy the media record to main_image collection
                        $this->copyMediaToMainImage($sourceMedia);
                        $processedCalendarIds[] = $sourceMedia->model_id;
                        $calendarsWithMainImage[] = $sourceMedia->model_id;
                    } catch (\Exception $e) {
                        Log::warning(
                            "Failed to copy gallery image to main_image for calendar {$sourceMedia->model_id}: {$e->getMessage()}"
                        );
                    }
                }
            });
    }

    /**
     * Copy a media record to the main_image collection without re-processing images.
     */
    private function copyMediaToMainImage(Media $sourceMedia): void
    {
        // Create new media record for main_image collection
        $newMedia = $sourceMedia->replicate();
        $newMedia->collection_name = 'main_image';
        $newMedia->save();

        // Copy the physical files
        $sourceDisk = $sourceMedia->disk;
        $sourceDirectory = $sourceMedia->id.'/';
        $targetDirectory = $newMedia->id.'/';

        $storage = \Illuminate\Support\Facades\Storage::disk($sourceDisk);

        // Copy all files from source to target directory
        $files = $storage->allFiles($sourceDirectory);
        foreach ($files as $file) {
            $newPath = str_replace($sourceDirectory, $targetDirectory, $file);
            $storage->copy($file, $newPath);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all main_image media for calendars
        $mainImageMedia = Media::where('model_type', 'App\\Models\\Calendar')
            ->where('collection_name', 'main_image')
            ->get();

        foreach ($mainImageMedia as $media) {
            try {
                // Delete the physical files
                $storage = \Illuminate\Support\Facades\Storage::disk($media->disk);
                $directory = $media->id.'/';

                if ($storage->exists($directory)) {
                    $storage->deleteDirectory($directory);
                }

                // Delete the media record
                $media->delete();
            } catch (\Exception $e) {
                Log::warning(
                    "Failed to remove main_image media {$media->id}: {$e->getMessage()}"
                );
            }
        }
    }
};
