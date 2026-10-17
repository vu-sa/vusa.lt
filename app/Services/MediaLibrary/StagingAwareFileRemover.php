<?php

namespace App\Services\MediaLibrary;

use App\Support\StagingProtection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;

class StagingAwareFileRemover extends DefaultFileRemover
{
    /**
     * Waits for commit: a rolled-back delete (e.g. the self-lockout guard) restores the row, and
     * its files must still be there.
     */
    public function removeAllFiles(Media $media): void
    {
        if (StagingProtection::diskIsWritable($media->disk) && StagingProtection::diskIsWritable($media->conversions_disk)) {
            DB::afterCommit(fn () => parent::removeAllFiles($media));
        }
    }

    public function removeResponsiveImages(Media $media, string $conversionName): void
    {
        if (StagingProtection::diskIsWritable($media->conversions_disk)) {
            parent::removeResponsiveImages($media, $conversionName);
        }
    }

    public function removeFile(string $path, string $disk): void
    {
        if (StagingProtection::diskIsWritable($disk)) {
            parent::removeFile($path, $disk);
        }
    }
}
