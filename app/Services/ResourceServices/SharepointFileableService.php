<?php

namespace App\Services\ResourceServices;

use App\Contracts\SharepointFileableContract;
use App\Models\SharepointFile;
use App\Models\Traits\HasSharepointFiles;

class SharepointFileableService
{
    /**
     * Attach a SharePoint file to a fileable model
     *
     * @param  SharepointFile  $sharepointFile
     * @param  SharepointFileableContract  $fileable
     * @return SharepointFileableContract
     */
    public function attachFileToFileable(SharepointFile $sharepointFile, SharepointFileableContract $fileable)
    {
        $fileable->files()->attach($sharepointFile->id);

        return $fileable;
    }
}
