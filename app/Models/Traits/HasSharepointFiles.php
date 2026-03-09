<?php

namespace App\Models\Traits;

use App\Contracts\SharepointFileableContract;
use App\Models\FileableFile;
use App\Models\SharepointFile;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Provides SharePoint file functionality to models.
 *
 * Models using this trait should implement SharepointFileableContract
 * for proper type safety when working with file operations.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FileableFile> $fileableFiles
 *
 * @mixin \Eloquent
 */
trait HasSharepointFiles
{
    /**
     * Legacy relationship: files via pivot table (sharepoint_fileables).
     *
     * @deprecated Use fileableFiles() instead for new implementations
     */
    public function files(): MorphToMany
    {
        return $this->morphToMany(SharepointFile::class, 'fileable', 'sharepoint_fileables');
    }

    /**
     * New relationship: files with local metadata storage.
     * Enables instant queries without SharePoint API calls.
     */
    public function fileableFiles(): MorphMany
    {
        return $this->morphMany(FileableFile::class, 'fileable');
    }

    /**
     * Get only available files (not deleted, not externally deleted).
     */
    public function availableFiles(): MorphMany
    {
        return $this->fileableFiles()
            ->whereNull('deleted_externally_at')
            ->whereNull('deleted_at');
    }

    /**
     * Check if this model has a file of a specific type.
     * Useful for badges like "has protokolas".
     */
    public function hasFileOfType(string $type): bool
    {
        return $this->fileableFiles()
            ->where('file_type', $type)
            ->whereNull('deleted_externally_at')
            ->exists();
    }

    /**
     * Check if this model has a protocol file.
     */
    public function getHasProtocolAttribute(): bool
    {
        // Use cached relationship if loaded
        if ($this->relationLoaded('fileableFiles')) {
            return $this->fileableFiles
                ->where('file_type', FileableFile::TYPE_PROTOCOL)
                ->whereNull('deleted_externally_at')
                ->isNotEmpty();
        }

        return $this->hasFileOfType(FileableFile::TYPE_PROTOCOL);
    }

    /**
     * Check if this model has a report file.
     */
    public function getHasReportAttribute(): bool
    {
        if ($this->relationLoaded('fileableFiles')) {
            return $this->fileableFiles
                ->where('file_type', FileableFile::TYPE_REPORT)
                ->whereNull('deleted_externally_at')
                ->isNotEmpty();
        }

        return $this->hasFileOfType(FileableFile::TYPE_REPORT);
    }

    /**
     * Get files of a specific type.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, FileableFile>
     */
    public function filesOfType(string $type)
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, FileableFile> $files */
        $files = $this->fileableFiles()
            ->where('file_type', $type)
            ->whereNull('deleted_externally_at')
            ->get();

        return $files;
    }

    /**
     * Get the SharePoint path for this fileable.
     * Uses human-readable paths with model names for easy navigation.
     */
    public function sharepoint_path(): string
    {
        return SharepointFileService::pathForFileableDriveItem($this);
    }
}
