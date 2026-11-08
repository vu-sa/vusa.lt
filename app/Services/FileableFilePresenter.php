<?php

namespace App\Services;

use App\Models\FileableFile;

class FileableFilePresenter
{
    /** @return array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null} */
    public static function file(FileableFile $file): array
    {
        $attributes = $file->attributesToArray();

        return [
            'id' => $attributes['id'],
            'fileable_type' => $attributes['fileable_type'],
            'fileable_id' => $attributes['fileable_id'],
            'sharepoint_id' => $attributes['sharepoint_id'],
            'sharepoint_path' => $attributes['sharepoint_path'],
            'name' => $attributes['name'],
            'file_type' => $attributes['file_type'],
            'mime_type' => $attributes['mime_type'],
            'size_bytes' => $attributes['size_bytes'],
            'file_date' => $attributes['file_date'],
            'description' => $attributes['description'],
            'public_link' => $attributes['public_link'],
            'public_link_expires_at' => $attributes['public_link_expires_at'],
            'last_synced_at' => $attributes['last_synced_at'],
            'deleted_externally_at' => $attributes['deleted_externally_at'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'formatted_size' => $attributes['formatted_size'],
        ];
    }
}
