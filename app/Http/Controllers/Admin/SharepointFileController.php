<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\CreateSharepointFolderRequest;
use App\Models\FileableFile;
use App\Models\SharepointFile;
use App\Services\SharepointGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

class SharepointFileController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', SharepointFile::class);

        $path = $request->input('path');

        $path ??= 'General';

        return $this->inertiaResponse('Admin/Files/IndexSharepoint', [
            'path' => $path,
        ]);
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
            Log::warning('Public permission creation rejected', [
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

            Log::error('Public permission creation failed', [
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
