<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\SharepointFile;
use App\Models\Type;
use App\Services\SharepointService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class SharepointFileService
{
    public function generateUniqueFolderName(string $fileable_id, string $fileable_name) {
        // name + last 4 characters of id
        $folderName = $fileable_name . '-' . substr($fileable_id, -4);

        return $folderName;
    }
    
    public function generatePathForFile(string $filename, Model $fileable, string $fileable_type) {
        $path = 'General';

        if ($fileable instanceof Type) {
            $path .= '/' . $fileable_type . '/' . $fileable->name;
        }

        if ($fileable instanceof Institution) {
            $padalinys = $fileable->load('padalinys')->padalinys;
            $path .= '/'. 'Padaliniai';
            $path .= '/' . $padalinys->shortname . '/' . $fileable->name;
        }

        $path .= '/' . $filename;

        return $path;
    }
    
    // sharepoint file concern - upload file, get id, update database and create record, update list item and that is it
    public function uploadFile(UploadedFile $file, Model $fileable, array $listItemProperties) {

        $sharepointService = new SharepointService();

        $filePath = $this->generatePathForFile($file->getClientOriginalName(), $fileable, $fileable->getMorphClass());

        $driveItem = $sharepointService->uploadDriveItem($filePath, $file->getContent());

        $sharepointFile = SharepointFile::create(['sharepoint_id' => $driveItem->getId()]);

        $listItem = $driveItem->getListItem() ?? abort(500);
        $sharepointService->updateListItem(config('filesystems.sharepoint.list_id'), $listItem->getId(), $listItemProperties);

        return $sharepointFile;
    }
}