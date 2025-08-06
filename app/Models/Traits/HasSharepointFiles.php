<?php

namespace App\Models\Traits;

use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\SharepointFile;
use App\Services\ResourceServices\SharepointFileService;

/**
 * Provides SharePoint file functionality to models
 *
 * Models using this trait should implement SharepointFileableContract
 * to ensure proper type safety with SharepointFileableService
 */

trait HasSharepointFiles
{
    public function files(): \Illuminate\Database\Eloquent\Relations\MorphToMany
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

    public function sharepoint_path(): string
    {
        return SharepointFileService::pathForFileableDriveItem($this);
    }
}
