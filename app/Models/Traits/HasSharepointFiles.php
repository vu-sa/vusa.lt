<?php

namespace App\Models\Traits;

use App\Events\FileableNameUpdated;
use App\Models\SharepointFile;
use App\Services\ResourceServices\SharepointFileService;

trait HasSharepointFiles
{
    public function files()
    {
        return $this->morphToMany(SharepointFile::class, 'fileable', 'sharepoint_fileables');
    }

    // protected static function bootHasSharepointDocuments()
    // {
    //     static::saved(function ($model) {
    //         // check if institution name $institution->getChanges()['name'] has changed
    //         if (array_key_exists('name', $model->getChanges())) {
    //             // dispatch event FileableNameUpdated
    //             FileableNameUpdated::dispatch($model);
    //         }
    //     });
    // }

    public function sharepoint_path()
    {
        return SharepointFileService::pathForFileableDriveItem($this);
    }
}
