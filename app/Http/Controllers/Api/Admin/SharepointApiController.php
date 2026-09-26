<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\GetTypeFiles;
use App\Contracts\SharepointFileableContract;
use App\Enums\AllowedFileablesEnum;
use App\Http\Controllers\Api\ApiController;
use App\Models\FileableFile;
use App\Models\SharepointFile;
use App\Services\SharepointGraphService;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SharepointApiController extends ApiController
{
    /**
     * Get files for a specific fileable model.
     * Returns locally stored FileableFile records.
     */
    public function fileableFiles(Request $request, string $type, string $id): JsonResponse
    {
        $this->requireAuth($request);

        if (AllowedFileablesEnum::classFor($type) === null) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        $fileable_class = AllowedFileablesEnum::classFor($type);

        /** @var Model|null $fileable */
        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return $this->jsonNotFound('Fileable not found');
        }

        if (! $fileable instanceof SharepointFileableContract) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        /** @var SharepointFileableContract $fileable */
        $this->authorizeApi('view', $fileable);

        $files = $fileable->availableFiles()
            ->orderBy('file_date', 'desc')
            ->get();

        return $this->jsonSuccess($files);
    }

    /**
     * Get files from associated Types for a fileable.
     * Enables viewing files from parent Types.
     */
    public function inheritedFiles(Request $request, string $type, string $id): JsonResponse
    {
        $this->requireAuth($request);

        if (AllowedFileablesEnum::classFor($type) === null) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        $fileable_class = AllowedFileablesEnum::classFor($type);

        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return $this->jsonNotFound('Fileable not found');
        }

        $this->authorizeApi('view', $fileable);

        $files = GetTypeFiles::forFileable($fileable);

        return $this->jsonSuccess($files);
    }

    /**
     * Get drive items from SharePoint.
     */
    public function driveItems(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $path = $request->input('path');
        $path = rtrim($path, '/');

        // Require authorization for SharePoint browsing
        $this->authorizeApi('viewAny', SharepointFile::class);

        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        // If fileable context is provided, attach FileableFile records
        $fileableType = $request->input('fileable_type');
        $fileableId = $request->input('fileable_id');

        $driveItems = $this->attachFileableFilesToDriveItems($driveItems, $fileableType, $fileableId);

        return $this->jsonSuccess($driveItems);
    }

    /**
     * Attach FileableFile records to drive items when fileable context is provided.
     *
     * @param  Collection<int, array>  $driveItems
     * @return Collection<int, array>
     */
    protected function attachFileableFilesToDriveItems(Collection $driveItems, ?string $fileableType, ?string $fileableId): Collection
    {
        if (! $fileableType || ! $fileableId || AllowedFileablesEnum::classFor($fileableType) === null) {
            return $driveItems;
        }

        $driveItemIds = $driveItems->pluck('id')->filter()->values()->toArray();

        if (empty($driveItemIds)) {
            return $driveItems;
        }

        $fileableFiles = FileableFile::where('fileable_type', MorphMap::alias(AllowedFileablesEnum::classFor($fileableType)))
            ->where('fileable_id', $fileableId)
            ->whereIn('sharepoint_id', $driveItemIds)
            ->whereNull('deleted_externally_at')
            ->get()
            ->keyBy('sharepoint_id');

        return $driveItems->map(function (array $item) use ($fileableFiles) {
            if (isset($item['id'])) {
                $item['fileableFile'] = $fileableFiles->get($item['id']);
            }

            return $item;
        });
    }
}
