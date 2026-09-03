<?php

namespace App\Services\MediaLibrary;

use App\Support\StagingProtection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;

class StagingAwareFileRemover extends DefaultFileRemover
{
    public function removeAllFiles(Media $media): void
    {
        if (! StagingProtection::filesAreReadOnly()) {
            parent::removeAllFiles($media);
        }
    }

    public function removeResponsiveImages(Media $media, string $conversionName): void
    {
        if (! StagingProtection::filesAreReadOnly()) {
            parent::removeResponsiveImages($media, $conversionName);
        }
    }

    public function removeFile(string $path, string $disk): void
    {
        if (! StagingProtection::filesAreReadOnly()) {
            parent::removeFile($path, $disk);
        }
    }
}
