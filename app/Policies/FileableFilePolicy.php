<?php

namespace App\Policies;

use App\Models\FileableFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for FileableFile model.
 *
 * File permissions are delegated to the parent fileable model's policy.
 * This ensures consistent access control across the application.
 */
class FileableFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any files.
     */
    public function viewAny(User $user): bool
    {
        // Users can view files if they can view any of the supported fileable types
        return true;
    }

    /**
     * Determine whether the user can view the file.
     * Delegates to the parent fileable's view permission.
     */
    public function view(User $user, FileableFile $fileableFile): bool
    {
        $fileable = $fileableFile->fileable;

        if (! $fileable) {
            return false;
        }

        return $user->can('view', $fileable);
    }

    /**
     * Determine whether the user can create files.
     * Requires ability to update the target fileable.
     */
    public function create(User $user): bool
    {
        // Creation is checked against the specific fileable in the controller
        return true;
    }

    /**
     * Determine whether the user can update the file.
     * Delegates to the parent fileable's update permission.
     */
    public function update(User $user, FileableFile $fileableFile): bool
    {
        $fileable = $fileableFile->fileable;

        if (! $fileable) {
            return false;
        }

        return $user->can('update', $fileable);
    }

    /**
     * Determine whether the user can delete the file.
     * Delegates to the parent fileable's update permission.
     */
    public function delete(User $user, FileableFile $fileableFile): bool
    {
        $fileable = $fileableFile->fileable;

        if (! $fileable) {
            return false;
        }

        // Deleting a file requires update permission on the fileable
        return $user->can('update', $fileable);
    }

    /**
     * Determine whether the user can restore the file.
     */
    public function restore(User $user, FileableFile $fileableFile): bool
    {
        return $this->delete($user, $fileableFile);
    }

    /**
     * Determine whether the user can permanently delete the file.
     */
    public function forceDelete(User $user, FileableFile $fileableFile): bool
    {
        return $this->delete($user, $fileableFile);
    }
}
