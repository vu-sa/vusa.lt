<?php

namespace App\Services\ResourceServices;

use App\Contracts\SharepointFileableContract;
use App\Models\SharepointFile;

class SharepointFileableService
{
    /**
     * Attach a SharePoint file to a fileable model
     *
     * @return SharepointFileableContract
     */
    public function attachFileToFileable(SharepointFile $sharepointFile, SharepointFileableContract $fileable)
    {
        $fileable->files()->syncWithoutDetaching([$sharepointFile->id]);

        return $fileable;
    }
}
