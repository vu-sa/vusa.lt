<?php

namespace App\Listeners;

use App\Events\FileableNameUpdated;
use App\Exceptions\SharepointFolderRenameException;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Type;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles SharePoint folder renaming when a fileable's name changes.
 *
 * This listener runs synchronously to ensure the database is only updated
 * if the SharePoint rename succeeds. If SharePoint fails, an exception is
 * thrown to prevent the model save.
 */
class UpdateSharepointFolder
{
    /**
     * Handle the event.
     *
     * Renames the SharePoint folder when a fileable's name changes.
     * This is necessary because we use human-readable paths that include model names.
     *
     * This runs during the 'saving' event (before DB commit), so we use isDirty()
     * to check for pending changes. If SharePoint rename fails, an exception is
     * thrown to prevent the model from being saved.
     */
    public function handle(FileableNameUpdated $event): void
    {
        // Skip SharePoint operations in testing environment
        if (app()->environment('testing')) {
            return;
        }

        $fileable = $event->fileable;

        // Skip new models - they don't have an existing SharePoint folder to rename
        if (! $fileable->exists) {
            return;
        }

        $dirty = $fileable->getDirty();
        $original = $fileable->getOriginal();

        // Determine the old and new folder names based on what changed
        $oldName = null;
        $newName = null;

        if ($fileable instanceof Meeting && $fileable->isDirty('start_time') && isset($original['start_time'])) {
            // Meeting folder name is the formatted datetime
            $oldName = Carbon::parse($original['start_time'])->format('Y-m-d H.i');
            $newName = Carbon::parse($dirty['start_time'])->format('Y-m-d H.i');
        } elseif ($fileable instanceof Duty && $fileable->isDirty('name') && isset($original['name'])) {
            // Duty name is translatable - use Lithuanian version for folder name
            $oldName = $this->getTranslatableValue($original['name'], 'lt');
            $newName = $this->getTranslatableValue($dirty['name'], 'lt');
        } elseif ($fileable instanceof Institution && $fileable->isDirty('name') && isset($original['name'])) {
            // Institution name is translatable - use Lithuanian version for folder name
            $oldName = $this->getTranslatableValue($original['name'], 'lt');
            $newName = $this->getTranslatableValue($dirty['name'], 'lt');
        } elseif ($fileable instanceof Type && $fileable->isDirty('title') && isset($original['title'])) {
            // Type title is translatable - use Lithuanian version for folder name
            $oldName = $this->getTranslatableValue($original['title'], 'lt');
            $newName = $this->getTranslatableValue($dirty['title'], 'lt');
        } else {
            // No relevant changes
            return;
        }

        // Skip if names are the same or missing
        if (! $oldName || ! $newName || $oldName === $newName) {
            return;
        }

        // Build the old path by temporarily setting the original values
        $tempFileable = $this->createTempFileableWithOriginal($fileable, $original);

        try {
            $oldPath = SharepointFileService::pathForFileableDriveItem($tempFileable);
        } catch (HttpException $e) {
            Log::warning('Could not determine old SharePoint path', [
                'error' => $e->getMessage(),
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->id ?? null,
            ]);

            return;
        }

        // Rename the folder in SharePoint
        $sharepointGraph = new SharepointGraphService(
            driveId: config('filesystems.sharepoint.vusa_drive_id')
        );

        // Check if folder exists before attempting rename
        // Folder may not exist if no files have been uploaded yet
        $existingFolder = $sharepointGraph->getDriveItemObjectByPath($oldPath);

        if ($existingFolder === null) {
            Log::info('SharePoint folder does not exist, skipping rename', [
                'old_path' => $oldPath,
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->id ?? null,
            ]);

            return;
        }

        $driveItem = $sharepointGraph->updateDriveItemByPath($oldPath, [
            'name' => $newName,
        ]);

        if ($driveItem === null) {
            Log::error('SharePoint folder rename failed - preventing model save', [
                'old_path' => $oldPath,
                'old_name' => $oldName,
                'new_name' => $newName,
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->id ?? null,
            ]);

            throw new SharepointFolderRenameException(
                oldPath: $oldPath,
                oldName: $oldName,
                newName: $newName,
                fileableType: get_class($fileable),
                fileableId: $fileable->id ?? null,
            );
        }

        Log::info('SharePoint folder renamed successfully', [
            'old_path' => $oldPath,
            'old_name' => $oldName,
            'new_name' => $newName,
            'fileable_type' => get_class($fileable),
            'fileable_id' => $fileable->id ?? null,
        ]);
    }

    /**
     * Extract a value from a translatable field.
     * Handles both array format and JSON string format.
     */
    private function getTranslatableValue(mixed $value, string $locale): ?string
    {
        if (is_array($value)) {
            return $value[$locale] ?? reset($value) ?: null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ?? reset($decoded) ?: null;
            }

            return $value;
        }

        return null;
    }

    /**
     * Create a temporary copy of the fileable with original values set.
     * This allows us to generate the old path before the rename.
     */
    private function createTempFileableWithOriginal(mixed $fileable, array $original): mixed
    {
        // Clone the model to avoid modifying the original
        $temp = clone $fileable;

        // Reset the changed attributes to their original values
        if ($fileable instanceof Meeting && $fileable->isDirty('start_time')) {
            // Use setRawAttributes to ensure the datetime is properly reset
            // without any cached Carbon instance interference
            $temp->setRawAttributes(array_merge(
                $temp->getAttributes(),
                ['start_time' => $original['start_time']]
            ));
        } elseif ($fileable instanceof Type && $fileable->isDirty('title')) {
            // For translatable models, the original value might be an array (in-memory)
            // or a JSON string (from database). We need to ensure it's stored as JSON
            // so getTranslation() can decode it properly.
            $originalTitle = $original['title'];
            if (is_array($originalTitle)) {
                $originalTitle = json_encode($originalTitle);
            }

            $temp->setRawAttributes(array_merge(
                $temp->getAttributes(),
                ['title' => $originalTitle]
            ));
        } elseif ($fileable->isDirty('name')) {
            // For translatable models (Institution, Duty), the original value might be
            // an array (in-memory) or a JSON string (from database).
            $originalName = $original['name'];
            if (is_array($originalName)) {
                $originalName = json_encode($originalName);
            }

            $temp->setRawAttributes(array_merge(
                $temp->getAttributes(),
                ['name' => $originalName]
            ));
        }

        return $temp;
    }
}
