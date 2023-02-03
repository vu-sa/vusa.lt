<?php

namespace App\Services\ResourceServices;

use App\Models\SharepointFile;
use Illuminate\Database\Eloquent\Model;

class SharepointFileableService
{
    public function attachFileToFileable(SharepointFile $sharepointFile, Model $fileable) {
        $fileable->files()->attach($sharepointFile->id);

        return $fileable;
    }
}