<?php

namespace App\Jobs;

use App\Models\FileableFile;
use App\Services\SharepointGraphService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync FileableFile records with SharePoint.
 *
 * This job checks if files still exist in SharePoint and marks
 * them as deleted_externally if they have been removed.
 *
 * Should be scheduled to run periodically (e.g., weekly).
 */
class SyncFileableFilesJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Optional: only sync files for a specific fileable type.
     */
    protected ?string $fileableType;

    /**
     * Optional: batch size for processing.
     */
    protected int $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $fileableType = null, int $batchSize = 50)
    {
        $this->fileableType = $fileableType;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sharepointService = new SharepointGraphService(
            driveId: config('filesystems.sharepoint.vusa_drive_id')
        );

        $query = FileableFile::query()
            ->whereNull('deleted_externally_at')
            ->whereNull('deleted_at');

        if ($this->fileableType) {
            $query->where('fileable_type', $this->fileableType);
        }

        $syncedCount = 0;
        $deletedCount = 0;
        $errorCount = 0;

        $query->chunkById($this->batchSize, function ($files) use ($sharepointService, &$syncedCount, &$deletedCount, &$errorCount) {
            foreach ($files as $file) {
                try {
                    $this->syncFile($file, $sharepointService, $syncedCount, $deletedCount);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::warning('Failed to sync FileableFile', [
                        'file_id' => $file->id,
                        'sharepoint_id' => $file->sharepoint_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('FileableFile sync completed', [
            'synced' => $syncedCount,
            'marked_deleted' => $deletedCount,
            'errors' => $errorCount,
            'fileable_type' => $this->fileableType ?? 'all',
        ]);
    }

    /**
     * Sync a single file with SharePoint.
     */
    protected function syncFile(
        FileableFile $file,
        SharepointGraphService $sharepointService,
        int &$syncedCount,
        int &$deletedCount
    ): void {
        $driveItem = $sharepointService->getDriveItemById($file->sharepoint_id);

        if (! $driveItem) {
            // File no longer exists in SharePoint
            $file->markAsDeletedExternally();
            $deletedCount++;

            Log::info('FileableFile marked as externally deleted', [
                'file_id' => $file->id,
                'name' => $file->name,
                'sharepoint_id' => $file->sharepoint_id,
            ]);

            return;
        }

        // Update metadata from SharePoint
        $listItem = $driveItem->getListItem();
        /** @var array<string, mixed> $fields */
        $fields = $listItem?->getFields()?->getAdditionalData() ?? [];

        $file->update([
            'name' => $driveItem->getName() ?? $file->name,
            'size_bytes' => $driveItem->getSize() ?? $file->size_bytes,
            'mime_type' => $driveItem->getFile()?->getMimeType() ?? $file->mime_type,
            'file_type' => $fields['Type'] ?? $file->file_type,
            'description' => $fields['Description0'] ?? $file->description,
            'last_synced_at' => now(),
        ]);

        $syncedCount++;
    }
}
