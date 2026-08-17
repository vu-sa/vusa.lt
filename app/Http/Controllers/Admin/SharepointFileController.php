<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SharepointFileableContract;
use App\Enums\AllowedFileablesEnum;
use App\Http\Controllers\AdminController;
use App\Http\Requests\CreateSharepointFolderRequest;
use App\Http\Requests\StoreSharepointFileRequest;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\SharepointFile;
use App\Models\Type;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

class SharepointFileController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', SharepointFile::class);

        $graph = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $path = $request->get('path');

        $path ??= 'General';

        return $this->inertiaResponse('Admin/Files/IndexSharepoint', [
            'path' => $path,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Now creates FileableFile records with local metadata.
     */
    public function store(StoreSharepointFileRequest $request)
    {
        $this->handleAuthorization('create', SharepointFile::class);

        $validated = $request->validated();

        $fileableType = $validated['fileable']['type'];

        // Only allow whitelisted fileable types
        if (AllowedFileablesEnum::classFor($fileableType) === null) {
            return back()->with('error', __('messages.sharepoint.not_fileable'));
        }

        $fileable_class = AllowedFileablesEnum::classFor($fileableType);

        // check if fileable exists
        $fileable = $fileable_class::find($validated['fileable']['id']);
        if (! $fileable) {
            return back()->with('error', __('messages.sharepoint.fileable_missing'));
        }

        // check if fileable is allowed to have files (new method)
        if (! method_exists($fileable, 'fileableFiles')) {
            return back()->with('error', __('messages.sharepoint.fileable_not_allowed'));
        }

        $sharepointFileService = new SharepointFileService;

        $listItemProperties = [
            'Type' => $validated['file']['typeValue'],
            'Description0' => $validated['file']['description0Value'],
            'Date' => date('Y-m-d', intval($validated['file']['datetimeValue'] / 1000)),
        ];

        // Only include Keywords if there are actual values - SharePoint rejects empty Collections
        $keywords = $validated['file']['keywordsValue'] ?? [];
        if (! empty($keywords)) {
            $listItemProperties['Keywords'] = $keywords;
            $listItemProperties['Keywords@odata.type'] = 'Collection(Edm.String)';
        }

        // Use new upload method that creates FileableFile record
        $uploadedFile = $request->file('file.uploadValue');
        $fileableFile = $sharepointFileService->uploadFile(
            $uploadedFile,
            $validated['file']['nameValue'],
            $fileable,
            $listItemProperties
        );

        return back()->with('success', __('messages.sharepoint.uploaded'));
    }

    /**
     * Remove the specified resource from storage.
     * Supports both legacy SharepointFile and new FileableFile.
     */
    public function destroy(Request $request, SharepointFile $sharepointFile)
    {
        $this->handleAuthorization('delete', $sharepointFile);

        $sharepointFileService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $sharepointFileService->deleteDriveItem($sharepointFile->sharepoint_id);

        // Also delete any associated FileableFile records
        FileableFile::where('sharepoint_id', $sharepointFile->sharepoint_id)->delete();

        return back()->with('info', __('messages.sharepoint.file_deleted'));
    }

    /**
     * Delete a FileableFile by its ID.
     */
    public function destroyFileableFile(Request $request, FileableFile $fileableFile)
    {
        $this->authorize('delete', $fileableFile);

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        try {
            $sharepointService->deleteDriveItem($fileableFile->sharepoint_id);
        } catch (\Exception) {
            // If SharePoint deletion fails, mark as externally deleted
            $fileableFile->markAsDeletedExternally();

            return back()->with('warning', __('messages.sharepoint.deleted_locally_only'));
        }

        $fileableFile->delete();

        return back()->with('info', __('messages.sharepoint.file_deleted'));
    }

    /**
     * Get files for a specific fileable model.
     * Returns locally stored FileableFile records (no SharePoint API call needed).
     */
    public function getFileableFiles(Request $request, string $type, string $id)
    {
        if (AllowedFileablesEnum::classFor($type) === null) {
            return response()->json(['error' => 'Invalid fileable type'], 400);
        }

        $fileable_class = AllowedFileablesEnum::classFor($type);

        /** @var Model|null $fileable */
        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return response()->json(['error' => 'Fileable not found'], 404);
        }

        if (! $fileable instanceof SharepointFileableContract) {
            return response()->json(['error' => 'Invalid fileable type'], 400);
        }

        /** @var SharepointFileableContract $fileable */
        $this->authorize('view', $fileable);

        $files = $fileable->availableFiles()
            ->orderBy('file_date', 'desc')
            ->get();

        return response()->json($files);
    }

    /**
     * Get files from associated Types for a fileable.
     * Enables viewing files from parent Types (e.g., all files for duties of type X).
     */
    public function getTypeInheritedFiles(Request $request, string $type, string $id)
    {
        if (AllowedFileablesEnum::classFor($type) === null) {
            return response()->json(['error' => 'Invalid fileable type'], 400);
        }

        $fileable_class = AllowedFileablesEnum::classFor($type);

        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return response()->json(['error' => 'Fileable not found'], 404);
        }

        $this->authorize('view', $fileable);

        // Check if fileable has types relationship
        if (! method_exists($fileable, 'types')) {
            return response()->json([]);
        }

        // Get all types including parents
        /** @var Collection<int, Type> $types */
        $types = $fileable->types()
            ->get()
            ->map(fn (Type $type) => $type->getParentsAndSelf())
            ->flatten()
            ->unique('id')
            ->values();

        // Get FileableFile records for all these types
        $typeIds = $types->pluck('id');

        $files = FileableFile::where('fileable_type', MorphMap::alias(Type::class))
            ->whereIn('fileable_id', $typeIds)
            ->available()
            ->orderBy('file_date', 'desc')
            ->get();

        return response()->json($files);
    }

    /**
     * Get potential fileables, usually when none specified.
     */
    public function getPotentialFileables(Request $request)
    {
        $this->handleAuthorization('viewAny', SharepointFile::class);

        return response()->json([
            'institutions' => Institution::with('meetings:meetings.id,start_time')->whereHas('tenant')->get()->map->only('id'),
            'types' => Type::all()->map->only('id'),
        ]);
    }

    public function getDriveItems(Request $request)
    {
        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $path = $request->get('path');

        // remove trailing slash
        $path = rtrim($path, '/');

        // Require SharePoint viewAny permission for drive browsing
        $this->handleAuthorization('viewAny', SharepointFile::class);

        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        return response()->json($driveItems);
    }

    /**
     * Create a new folder in SharePoint.
     */
    public function createFolder(CreateSharepointFolderRequest $request)
    {
        $this->handleAuthorization('create', SharepointFile::class);

        $validated = $request->validated();

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        try {
            $folderPath = rtrim($validated['path'], '/').'/'.$validated['name'];
            $sharepointService->createFolder($folderPath);

            return response()->json(['success' => true, 'message' => $this->entityMessage('created', 'folder')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Nepavyko sukurti aplanko: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get drive items from SharePoint for types associated with a fileable.
     * This is used for the SimpleFileViewer to show Type-inherited files.
     */
    public function getTypesDriveItems(Request $request, string $type, string $id)
    {
        if (AllowedFileablesEnum::classFor($type) === null) {
            return back()->with('info', __('messages.sharepoint.invalid_request'));
        }

        $fileable_class = AllowedFileablesEnum::classFor($type);

        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return back()->with('info', __('messages.sharepoint.invalid_request'));
        }

        $this->authorize('view', $fileable);

        // Only Duty and Institution carry a types() relation; Meeting and Type are equally
        // valid fileables but have none, and this used to fatal on them. Mirrors the guard in
        // getTypeInheritedFiles().
        if (! method_exists($fileable, 'types')) {
            return response()->json([]);
        }

        $types = $fileable->types()->get()->map(fn ($type) => $type->getParentsAndSelf())->flatten()->unique('id')->values();

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        // get all types paths into one array
        $paths = $types->map(fn ($type) => $type->sharepoint_path())->toArray();

        if (empty($paths)) {
            return response()->json([]);
        }

        $driveItems = $sharepointService->getDriveItemsChildrenByPaths($paths);

        return response()->json($driveItems);
    }

    public function getDriveItemPublicLink(Request $request, string $driveItemId)
    {
        // A drive item id is an opaque Graph identifier with no local model behind it, so this
        // is gated on the SharepointFile capability rather than on an object — same as createFolder().
        $this->handleAuthorization('viewAny', SharepointFile::class);

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $permission = $sharepointService->getDriveItemPublicLink($driveItemId);

        if (! $permission) {
            return response()->json(null);
        }

        $link = $permission->getLink()->getWebUrl();

        return response()->json($link);
    }

    public function createPublicPermission(Request $request, string $driveItemId)
    {
        // Mints an anonymous, non-expiring public link, so it must be gated at least as
        // tightly as creating a SharePoint file. See getDriveItemPublicLink() on why this is
        // a capability check rather than an object check.
        $this->handleAuthorization('create', SharepointFile::class);

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        try {
            $permission = $sharepointService->createPublicPermission(
                siteId: $sharepointService->siteId,
                driveItemId: $driveItemId,
                datetime: false  // Consistent with Document sync behavior - no expiration
            );

            return response()->json([
                'success' => true,
                'permission' => $permission,
                'url' => $permission->getLink()->getWebUrl(),
            ]);
        } catch (\InvalidArgumentException $e) {
            \Log::warning('Public permission creation rejected', [
                'drive_item_id' => $driveItemId,
                'reason' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Try to extract OData error details
            $errorMessage = 'Failed to create public permission';

            if ($e instanceof ODataError) {
                $odataMessage = $e->getError()?->getMessage();
                if ($odataMessage) {
                    $errorMessage = $odataMessage;
                }
            }

            \Log::error('Public permission creation failed', [
                'drive_item_id' => $driveItemId,
                'error' => $e->getMessage(),
                'odata_error' => $errorMessage,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
            ], 500);
        }
    }
}
