<?php

namespace App\Http\Controllers;

use App\Models\SharepointDocument;
use App\Services\SharepointAppGraph;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Microsoft\Graph\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SharepointController extends Controller
{
    public function index() {
        $graph = new SharepointAppGraph();
        
        $drive = $graph->getDriveBySite(config('filesystems.sharepoint.site_id'));
        $driveChildren = $graph->getDriveChildren($drive->getId());

        // for test get only the first one
        $driveItem_General = $driveChildren[0];
        $driveItems = $graph->getDriveItemChildren($drive->getId(), $driveItem_General->getId());

        $sharepointFiles = collect($driveItems)->map(function (Model\DriveItem $item) {
            return [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'webUrl' => $item->getWebUrl(),
                'createdDateTime' => $item->getCreatedDateTime(),
                'lastModifiedDateTime' => $item->getLastModifiedDateTime(),
                'size' => $item->getSize(),
                'file' => $item->getFile(),
                'folder' => $item->getFolder(),
                'type' => $item->getListItem()->getFields()->getProperties()['Type'] ?? null,
                'keywords' => $item->getListItem()->getFields()->getProperties()['Keywords'] ?? null,
                // get date +3 hours and format YYYY-MM-DD
                'date' => ($item->getListItem()->getFields()->getProperties()['Date'] ?? null) ? date('Y-m-d', strtotime($item->getListItem()->getFields()->getProperties()['Date'] . ' +3 hours')) : null,
            ];
        });

        // $termStore_Pagrindinis = $graph->getGlobalTermStore()->getProperties();
        // $termStore_Pagrindinis = collect($termStore_Pagrindinis['children'])->map(function ($term) {
        //     return [
        //         'label' => $term['labels'][0]['name'],
        //         'value' => $term['labels'][0]['name'],
        //     ];
        // });

        return Inertia::render('Admin/Sharepoint/Index', [
            'sharepointFiles' => $sharepointFiles,
        ]);
    }

    public function getFilesFromDocumentIds(Request $request) {
        // TODO: should consider a function instead, to get files from sharepoint ids
        $graph = new SharepointAppGraph();
        
        $documents = SharepointDocument::find($request->documentIds);

        $sharepointFiles = $graph->collectSharepointFiles($documents);

        return response()->json($sharepointFiles);
    }

    protected function getFilesFromModel(array $dataArray): Collection {
        $graph = new SharepointAppGraph();

        $modelId = $dataArray['documentable_id'];
        $modelType = $dataArray['documentable_type'];

        if (Cache::has('sharepoint_files_' . $modelType . '_' . $modelId)) 
        {
            return response()->json(Cache::get('sharepoint_files_' . $modelType . '_' . $modelId));
        }

        $model = $modelType::find($modelId);

        $sharepointFiles = $graph->collectSharepointFiles($model->documents);

        Cache::put('sharepoint_files_' . $modelType . '_' . $modelId, $sharepointFiles, 60 * 60);

        return $sharepointFiles;
    }

    public function addFile(Request $request) {
        $graph = new SharepointAppGraph();

        $fileToUpload = $request->files->all()['uploadValue'][0];

        $site = $graph->getSiteById(config('filesystems.sharepoint.site_id'));
        $drive = $graph->getDriveBySite($site->getId());

        $driveChildren = $graph->getDriveChildren($drive->getId());

        // for test get only the first one
        $driveItem_General = $driveChildren[0];

        $listItemInfo = [
            'Type' => $request->input('typeValue'),
            'Keywords' => $request->input('keywordsValue'),
            'Description0' => $request->input('descriptionValue'),
            'Keywords@odata.type' => "Collection(Edm.String)",
            'Date' => date('Y-m-d', intval($request->input('datetimeValue') / 1000)),
        ];

        $file = $fileToUpload['file'];
        $uploadedFile = $this->uploadFile($graph, $site, $drive, $driveItem_General, $file, $request->input('nameValue') ?? $file->getClientOriginalName(), $listItemInfo);

        $uploadedFileId = $uploadedFile->getId();

        $contentModel = $request->input('contentModel');

        SharepointDocument::create([
            'sharepoint_id' => $uploadedFileId,
            'documentable_id' => $contentModel['id'],
            'documentable_type' => $contentModel['type'],
        ]);

        // remove cache
        Cache::forget('sharepoint_files_' . $contentModel['type'] . '_' . $contentModel['id']);

        return redirect()->back()->with('success', 'Failas įkeltas');
    }

    public function addManyFiles(Request $request) {
        $graph = new SharepointAppGraph();

        $uploadedFiles = $request->files->all()['uploadValue'];

        // upload file to sharepoint
        $site = $graph->getSiteById(config('filesystems.sharepoint.site_id'));
        $drive = $graph->getDriveBySite($site->getId());

        $driveChildren = $graph->getDriveChildren($drive->getId());

        // for test get only the first one
        $driveItem_General = $driveChildren[0];

        $listItemInfo = [
            'Type' => $request->input('typeValue'),
            'Keywords' => $request->input('keywordsValue'),
            'Keywords@odata.type' => "Collection(Edm.String)",
            'Date' => date('Y-m-d', intval($request->input('datetimeValue') / 1000)),
        ];

        foreach ($uploadedFiles as $uploadedFile) {
            $file = $uploadedFile['file'];
            $this->uploadFile($graph, $site, $drive, $driveItem_General, $file, $file->getClientOriginalName(), $listItemInfo);
        }

        return redirect()->back()->with('success', 'Failas įkeltas');
    }

    private function uploadFile(SharepointAppGraph $graph, Model\Site $site, Model\Drive $drive, Model\DriveItem $driveItem_General, UploadedFile $file, string $filename, array $listItemInfo) : Model\DriveItem {
        
        $uploadedFile = $graph->uploadDriveItem($drive->getId(), $driveItem_General->getId(), $filename, $file->getContent());
        
        $uploadedDriveItem = $graph->getDriveItemByIdWithListItem($drive->getId(), $uploadedFile->getId());

        $listItem = $uploadedDriveItem->getListItem() ?? abort(500);
        
        $graph->updateListItem($site->getId(), config('filesystems.sharepoint.list_id'), $listItem->getId(), $listItemInfo);

        return $uploadedFile;
    }

    public function destroyFile($id) {
        $graph = new SharepointAppGraph();

        $site = $graph->getSiteById(config('filesystems.sharepoint.site_id'));
        $drive = $graph->getDriveBySite($site->getId());

        $graph->deleteDriveItem($drive->getId(), $id);

        // delete from database
        $deletedSharepointDocument = SharepointDocument::where('sharepoint_id', $id)->first();

        // remove cache
        Cache::forget('sharepoint_files_' . $deletedSharepointDocument->documentable_type . '_' . $deletedSharepointDocument->documentable_id);

        $deletedSharepointDocument->delete();

        return redirect()->back()->with('success', 'Failas ištrintas');
    }
}
