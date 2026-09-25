<?php

namespace App\Services\ResourceServices;

use App\Enums\SharepointFolderEnum;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Type;
use App\Services\SharepointGraphService;
use App\Support\StagingProtection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Microsoft\Kiota\Abstractions\ApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SharepointFileService
{
    private ?SharepointGraphService $graph = null;

    /** Resolved through the container so tests can bind a fake instead of reaching Graph. */
    protected function graph(): SharepointGraphService
    {
        return $this->graph ??= app(SharepointGraphService::class, ['driveId' => config('filesystems.sharepoint.vusa_drive_id')]);
    }

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
        if (! in_array(HasSharepointFiles::class, class_uses_recursive($fileable))) {
            abort(500, 'Model does not have HasSharepointFiles trait');
        }

        $path = SharepointFolderEnum::GENERAL->label();

        if ($fileable instanceof Type) {
            // model_type / getMorphClass() are morph aliases; the folder names are the model
            // names ("Types/News"), so both are studly-cased back before pluralizing.
            $typeableType = Str::plural(Str::studly((string) $fileable->model_type));

            $path .= '/'.Str::plural(Str::studly($fileable->getMorphClass()));
            $path .= '/'.$typeableType;
            $path .= '/'.$fileable->title;
        }

        if ($fileable instanceof Institution) {
            $tenant = $fileable->load('tenant')->tenant;

            if (! isset($tenant)) {
                abort(500, 'Institution does not have a tenant. Tenant must be assigned.');
            }

            $path .= '/'.SharepointFolderEnum::PADALINIAI->label();
            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename(Institution::class)).'/'.$fileable->getTranslation('name', 'lt');
        }

        if ($fileable instanceof Meeting) {
            $institution = $fileable->load('institutions.tenant')->institutions->first();
            $formattedDatetime = Carbon::parse($fileable->start_time)->format('Y-m-d H.i');

            if (! isset($institution)) {
                abort(500, 'Meeting does not have an institution. Institution must be assigned.');
            }

            $path .= '/'.SharepointFolderEnum::PADALINIAI->label();

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

            $path .= '/'.SharepointFolderEnum::PADALINIAI->label();
            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename($institution)).'/'.$institution->name;
            $path .= '/'.Str::plural(class_basename($fileable)).'/'.$fileable->getTranslation('name', 'lt');
        }

        return $path;
    }

    /**
     * Null when the record cannot have a folder yet (e.g. its institution has no tenant),
     * which is what hides the upload action instead of a 500 on the page.
     */
    public static function pathOrNull(Model $fileable): ?string
    {
        try {
            return self::pathForFileableDriveItem($fileable);
        } catch (HttpException) {
            return null;
        }
    }

    /**
     * Upload file to SharePoint and create local FileableFile record.
     */
    public function uploadFile(UploadedFile $file, string $filename, Model $fileable, array $listItemProperties): FileableFile
    {
        StagingProtection::ensureSharepointIsWritable(
            config('filesystems.sharepoint.site_id'),
            config('filesystems.sharepoint.vusa_drive_id'),
        );

        $sharepointService = $this->graph();

        $folderPath = self::pathForFileableDriveItem($fileable);
        $filePath = $folderPath.'/'.$filename;

        $driveItem = $sharepointService->uploadDriveItem($filePath, $file);

        // Create local FileableFile record with metadata
        $fileableFile = FileableFile::create([
            'fileable_type' => $fileable->getMorphClass(),
            'fileable_id' => $fileable->getKey(),
            'sharepoint_id' => $driveItem->getId(),
            // Graph renames on conflict ("x (1).pdf"), so keep the name it actually stored.
            'sharepoint_path' => $folderPath.'/'.($driveItem->getName() ?? $filename),
            'name' => $driveItem->getName() ?? $filename,
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
                Log::warning('Failed to update SharePoint list item metadata, but file was uploaded successfully', [
                    'fileableFile' => $fileableFile->id,
                    'sharepoint_id' => $driveItem->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $fileableFile;
    }

    /**
     * The anonymous view link readers open the file with — they hold no VU SA tenant account,
     * so SharePoint's own webUrl is useless to them. Reuses an existing link before minting one.
     */
    public function publicLinkFor(FileableFile $file): string
    {
        if ($file->public_link && ! $file->hasExpiredPublicLink()) {
            return $file->public_link;
        }

        $graph = $this->graph();

        $permission = $graph->getDriveItemPublicLink($file->sharepoint_id)
            ?? $graph->createPublicPermission($graph->siteId, $file->sharepoint_id, false);

        $url = $permission->getLink()?->getWebUrl();

        if ($url === null) {
            throw new \RuntimeException('SharePoint returned a sharing link without a URL.');
        }

        $file->update(['public_link' => $url, 'public_link_expires_at' => null]);

        return $url;
    }

    public function deleteFile(FileableFile $file): void
    {
        $this->graph()->deleteDriveItem($file->sharepoint_id);
    }

    public static function isNotFound(\Throwable $e): bool
    {
        return $e instanceof ApiException && $e->getResponseStatusCode() === 404;
    }
}
