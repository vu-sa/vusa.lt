<?php

namespace App\Services\ResourceServices;

use App\Enums\SharepointFolderEnum;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Type;
use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SharepointFileService
{
    public function generateUniqueFolderName(string $fileable_id, string $fileable_name)
    {
        // name + last 4 characters of id
        $folderName = $fileable_name.'-'.substr($fileable_id, -4);

        return $folderName;
    }

    /**
     * Get the human-readable SharePoint path for a fileable.
     * This path uses model names and is easier to navigate in SharePoint.
     *
     * Note: This path may change if model names change. The UpdateSharepointFolder
     * listener handles renaming folders when model names are updated.
     */
    public static function pathForFileableDriveItem(Model $fileable): string
    {
        // check if model has a trait HasSharepointFiles
        if (! in_array(\App\Models\Traits\HasSharepointFiles::class, class_uses_recursive($fileable))) {
            abort(500, 'Model does not have HasSharepointFiles trait');
        }

        $path = SharepointFolderEnum::GENERAL()->label;

        if ($fileable instanceof Type) {
            $typeableType = Str::plural(class_basename($fileable->model_type));

            $path .= '/'.Str::plural(class_basename($fileable->getMorphClass()));
            $path .= '/'.$typeableType;
            $path .= '/'.$fileable->title;
        }

        if ($fileable instanceof Institution) {
            $tenant = $fileable->load('tenant')->tenant;

            if (! isset($tenant)) {
                abort(500, 'Institution does not have a tenant. Tenant must be assigned.');
            }

            $path .= '/'.SharepointFolderEnum::PADALINIAI()->label;
            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename(Institution::class)).'/'.$fileable->getTranslation('name', 'lt');
        }

        if ($fileable instanceof Meeting) {
            $institution = $fileable->load('institutions.tenant')->institutions->first();
            $formattedDatetime = Carbon::parse($fileable->start_time)->format('Y-m-d H.i');

            if (! isset($institution)) {
                abort(500, 'Meeting does not have an institution. Institution must be assigned.');
            }

            $path .= '/'.SharepointFolderEnum::PADALINIAI()->label;

            $tenant = $institution->tenant;

            if (! isset($tenant)) {
                abort(500, 'Institution does not have a tenant. Tenant must be assigned.');
            }

            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename($institution)).'/'.$institution->name;
            $path .= '/'.Str::plural(class_basename($fileable)).'/'.$formattedDatetime;
        }

        if ($fileable instanceof Duty) {
            $institution = $fileable->load('institution.tenant')->institution;

            if (! isset($institution)) {
                abort(500, 'Duty does not have an institution. Institution must be assigned.');
            }

            $tenant = $institution->tenant;

            if (! isset($tenant)) {
                abort(500, 'Institution does not have a tenant. Tenant must be assigned.');
            }

            $path .= '/'.SharepointFolderEnum::PADALINIAI()->label;
            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename($institution)).'/'.$institution->name;
            $path .= '/'.Str::plural(class_basename($fileable)).'/'.$fileable->getTranslation('name', 'lt');
        }

        return $path;
    }

    /**
     * Upload file to SharePoint and create local FileableFile record.
     */
    public function uploadFile(UploadedFile $file, string $filename, Model $fileable, array $listItemProperties): FileableFile
    {
        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $folderPath = self::pathForFileableDriveItem($fileable);
        $filePath = $folderPath.'/'.$filename;

        $driveItem = $sharepointService->uploadDriveItem($filePath, $file);

        // Create local FileableFile record with metadata
        $fileableFile = FileableFile::create([
            'fileable_type' => $fileable->getMorphClass(),
            'fileable_id' => $fileable->getKey(),
            'sharepoint_id' => $driveItem->getId(),
            'sharepoint_path' => $filePath,
            'name' => $filename,
            'file_type' => $listItemProperties['Type'] ?? null,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'file_date' => isset($listItemProperties['Date']) ? Carbon::parse($listItemProperties['Date']) : null,
            'description' => $listItemProperties['Description0'] ?? null,
            'last_synced_at' => now(),
        ]);

        // Update SharePoint list item metadata (best effort - don't fail upload if metadata update fails)
        $listItem = $driveItem->getListItem();
        if ($listItem && ! empty($listItemProperties)) {
            try {
                $sharepointService->updateListItem(config('filesystems.sharepoint.list_id'), $listItem->getId(), $listItemProperties);
            } catch (\Exception $e) {
                // Log warning but don't fail - file is uploaded and local record has the metadata
                \Log::warning('Failed to update SharePoint list item metadata, but file was uploaded successfully', [
                    'fileableFile' => $fileableFile->id,
                    'sharepoint_id' => $driveItem->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $fileableFile;
    }
}
