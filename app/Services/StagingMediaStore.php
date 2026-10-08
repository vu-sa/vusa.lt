<?php

namespace App\Services;

use App\Support\StagingProtection;
use Illuminate\Support\Facades\File;

/**
 * Staging's own media disk. Its rows come and go with each production restore, so its files must
 * go too, or the next night's media ids would land on yesterday's files.
 */
class StagingMediaStore
{
    public function root(): string
    {
        return (string) config('filesystems.disks.'.StagingProtection::STAGING_MEDIA_DISK.'.root');
    }

    /**
     * Why the root is unsafe to empty, or null when it is staging's own folder. public/uploads links
     * to production's storage, so a root that resolves under it would wipe production files.
     */
    public function rootError(): ?string
    {
        $root = realpath($this->root());

        if ($root === false) {
            return null;
        }

        $public = realpath(public_path());
        $uploads = realpath(public_path('uploads'));

        if ($public === false || ! str_starts_with($root, $public.DIRECTORY_SEPARATOR)) {
            return 'The staging media disk must live inside this application\'s public directory.';
        }

        if ($uploads !== false && ($root === $uploads || str_starts_with($root, $uploads.DIRECTORY_SEPARATOR))) {
            return 'The staging media disk must not resolve into public/uploads, which is shared with production.';
        }

        return null;
    }

    public function wipe(): bool
    {
        if ($this->rootError() !== null) {
            return false;
        }

        if (File::isDirectory($this->root())) {
            File::cleanDirectory($this->root());
        }

        return true;
    }
}
