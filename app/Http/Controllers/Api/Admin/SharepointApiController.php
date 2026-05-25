<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\SharepointFileableContract;
use App\Http\Controllers\Api\ApiController;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\SharepointFile;
use App\Models\Type;
use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Allowed fileable model types for SharePoint file API operations.
 */
const ALLOWED_FILEABLE_TYPES = [
    'Duty' => Duty::class,
    'Type' => Type::class,
    'Meeting' => Meeting::class,
    'Institution' => Institution::class,
];

class SharepointApiController extends ApiController
{
    /**
     * Get files for a specific fileable model.
     * Returns locally stored FileableFile records.
     */
    public function fileableFiles(Request $request, string $type, string $id): JsonResponse
    {
        $this->requireAuth($request);

        if (! isset(ALLOWED_FILEABLE_TYPES[$type])) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        $fileable_class = ALLOWED_FILEABLE_TYPES[$type];

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

        if (! isset(ALLOWED_FILEABLE_TYPES[$type])) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        $fileable_class = ALLOWED_FILEABLE_TYPES[$type];

        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return $this->jsonNotFound('Fileable not found');
        }

        $this->authorizeApi('view', $fileable);

        // Check if fileable has types relationship
        if (! method_exists($fileable, 'types')) {
            return $this->jsonSuccess([]);
        }

        // Get all types including parents
        /** @var Collection<int, Type> $types */
        $types = $fileable->types()
            ->get()
            ->map(function (Type $type) {
                return $type->getParentsAndSelf();
            })
            ->flatten()
            ->unique('id')
            ->values();

        $typeIds = $types->pluck('id');

        $files = FileableFile::where('fileable_type', Type::class)
            ->whereIn('fileable_id', $typeIds)
            ->available()
            ->orderBy('file_date', 'desc')
            ->get();

        return $this->jsonSuccess($files);
    }

    /**
     * Get potential fileables (institutions and types).
     */
    public function potentialFileables(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        return $this->jsonSuccess([
            'institutions' => Institution::with('meetings:meetings.id,start_time')
                ->whereHas('tenant')
                ->get()
                ->map
                ->only('id', 'name', 'meetings'),
            'types' => Type::all()->map->only('id', 'title'),
        ]);
    }

    /**
     * Get drive items from SharePoint.
     */
    public function driveItems(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $sharepointService = new SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        $path = $request->get('path');
        $path = rtrim($path, '/');

        // Require authorization for SharePoint browsing
        $this->authorizeApi('viewAny', SharepointFile::class);

        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        // If fileable context is provided, attach FileableFile records
        $fileableType = $request->get('fileable_type');
        $fileableId = $request->get('fileable_id');

        $driveItems = $this->attachFileableFilesToDriveItems($driveItems, $fileableType, $fileableId);

        return $this->jsonSuccess($driveItems);
    }

    /**
     * Attach FileableFile records to drive items when fileable context is provided.
     *
     * @param  \Illuminate\Support\Collection<int, array>  $driveItems
     * @return \Illuminate\Support\Collection<int, array>
     */
    protected function attachFileableFilesToDriveItems(\Illuminate\Support\Collection $driveItems, ?string $fileableType, ?string $fileableId): \Illuminate\Support\Collection
    {
        if (! $fileableType || ! $fileableId || ! isset(ALLOWED_FILEABLE_TYPES[$fileableType])) {
            return $driveItems;
        }

        $driveItemIds = $driveItems->pluck('id')->filter()->values()->toArray();

        if (empty($driveItemIds)) {
            return $driveItems;
        }

        $fileableFiles = FileableFile::where('fileable_type', ALLOWED_FILEABLE_TYPES[$fileableType])
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
