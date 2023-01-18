<?php

namespace App\Models\Traits;

use App\Models\SharepointFile;

trait HasSharepointFiles {
    public function files()
    {
        return $this->morphToMany(SharepointFile::class, 'fileable', 'sharepoint_fileables');
    }
}