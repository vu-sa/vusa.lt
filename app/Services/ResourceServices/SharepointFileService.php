<?php

namespace App\Services\ResourceServices;

use App\Models\Doing;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\SharepointFile;
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

    public static function pathForFileableDriveItem(Model $fileable)
    {
        // check if model has a trait HasSharepointFiles
        if (! in_array(\App\Models\Traits\HasSharepointFiles::class, class_uses($fileable))) {
            abort(500, 'Model does not have HasSharepointFiles trait');
        }

        $path = 'General';

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

            $path .= '/'.'Padaliniai';
            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename($fileable->getMorphClass())).'/'.$fileable->name;
        }

        if ($fileable instanceof Meeting) {
            $institution = $fileable->load('institutions.tenant')->institutions->first();
            $formattedDatetime = Carbon::parse($fileable->start_time)->format('Y-m-d H.i');

            if (! isset($institution)) {
                abort(500, 'Meeting does not have an institution. Institution must be assigned.');
            }

            $path .= '/'.'Padaliniai';

            $tenant = $institution->tenant;

            if (! isset($tenant)) {
                abort(500, 'Institution does not have a tenant. Tenant must be assigned.');
            }

            $path .= '/'.$tenant->shortname;
            $path .= '/'.Str::plural(class_basename($institution)).'/'.$institution->name;
            $path .= '/'.Str::plural(class_basename($fileable)).'/'.$formattedDatetime.'/'.$fileable->name;
        }

        if ($fileable instanceof Doing) {
            $fileable->loadMissing('users');

            $path .= '/'.'Users';
            $path .= '/'.$fileable->users->first()->name;
            $path .= '/'.Str::plural(class_basename($fileable->getMorphClass()));
            // add last 4 id letters

            if ($fileable->drive_item_name) {
                $path .= '/'.$fileable->drive_item_name;
            } else {
                $path .= '/'.$fileable->title.'-'.substr($fileable->id, -4);
                // update doing
                $fileable->drive_item_name = $fileable->title.'-'.substr($fileable->id, -4);
                $fileable->save();
            }
        }

        if ($fileable instanceof Goal) {
            $fileable->loadMissing('institutions.tenant');

            $institution = $fileable->institutions->first();

            $path .= '/'.'Padaliniai';
            $path .= '/'.$institution->tenant->shortname;
            $path .= '/'.Str::plural(class_basename($institution)).'/'.$institution->name;
            $path .= '/'.Str::plural(class_basename($fileable->getMorphClass())).'/'.$fileable->title;
        }

        return $path;
    }

    // sharepoint file concern - upload file, get id, update database and create record, update list item and that is it
    public function uploadFile(UploadedFile $file, string $filename, Model $fileable, array $listItemProperties)
    {
        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $filePath = self::pathForFileableDriveItem($fileable).'/'.$filename;

        $driveItem = $sharepointService->uploadDriveItem($filePath, $file);

        $sharepointFile = SharepointFile::create(['sharepoint_id' => $driveItem->getId()]);

        $listItem = $driveItem->getListItem() ?? abort(404, 'Sharepoint file does not have a list item');
        $sharepointService->updateListItem(config('filesystems.sharepoint.list_id'), $listItem->getId(), $listItemProperties);

        return $sharepointFile;
    }
}
