<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a SharePoint folder rename operation fails.
 * This prevents the model from being saved with mismatched folder names.
 */
class SharepointFolderRenameException extends Exception
{
    public function __construct(
        public readonly string $oldPath,
        public readonly string $oldName,
        public readonly string $newName,
        public readonly string $fileableType,
        public readonly mixed $fileableId,
    ) {
        parent::__construct(
            "Failed to rename SharePoint folder from '{$oldName}' to '{$newName}' at path '{$oldPath}'"
        );
    }
}
