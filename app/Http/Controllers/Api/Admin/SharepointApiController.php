<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Type;
use App\Services\SharepointGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Allowed fileable model types for SharePoint file API operations.
 */
const ALLOWED_FILEABLE_TYPES = [
    'Duty' => \App\Models\Duty::class,
    'Type' => \App\Models\Type::class,
    'Meeting' => \App\Models\Meeting::class,
    'Institution' => \App\Models\Institution::class,
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

        /** @var \Illuminate\Database\Eloquent\Model|null $fileable */
        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return $this->jsonNotFound('Fileable not found');
        }

        if (! $fileable instanceof \App\Contracts\SharepointFileableContract) {
            return $this->jsonError('Invalid fileable type', 400, code: 'INVALID_TYPE');
        }

        /** @var \App\Contracts\SharepointFileableContract $fileable */
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
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types */
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
        $this->authorizeApi('viewAny', \App\Models\SharepointFile::class);

        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        return $this->jsonSuccess($driveItems);
    }
}
