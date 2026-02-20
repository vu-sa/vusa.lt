<?php

namespace App\Jobs;

use App\Services\SharepointGraphService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

class RevokeSharepointPermissionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     *
     * Uses scalar parameters instead of model since the document may be deleted by the time the job runs.
     */
    public function __construct(
        public string $sharepointSiteId,
        public string $sharepointListId,
        public string $sharepointId,
        public string $sharepointPermissionId,
        public int $documentId,
    ) {
        $this->queue = 'sharepoint-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Revoking SharePoint permission for document', [
            'document_id' => $this->documentId,
            'permission_id' => $this->sharepointPermissionId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $graph = new SharepointGraphService(
                siteId: $this->sharepointSiteId,
                driveId: config('filesystems.sharepoint.archive_drive_id'),
                listId: $this->sharepointListId,
            );

            $driveItem = $graph->getDriveItemByListItem(
                $this->sharepointSiteId,
                $this->sharepointListId,
                $this->sharepointId,
            );

            $graph->deletePermission($driveItem->getId(), $this->sharepointPermissionId);

            Log::info('SharePoint permission revoked successfully', [
                'document_id' => $this->documentId,
                'permission_id' => $this->sharepointPermissionId,
            ]);
        } catch (ODataError $e) {
            // 404 means item or permission already deleted - that's fine
            if ($e->getError()?->getCode() === 'itemNotFound') {
                Log::info('SharePoint item or permission already deleted, skipping revocation', [
                    'document_id' => $this->documentId,
                    'permission_id' => $this->sharepointPermissionId,
                ]);

                return;
            }

            throw $e;
        } catch (\RuntimeException $e) {
            // getDriveItemByListItem throws RuntimeException for missing items
            if (str_contains($e->getMessage(), 'not found or inaccessible')) {
                Log::info('SharePoint item not found, skipping revocation', [
                    'document_id' => $this->documentId,
                    'permission_id' => $this->sharepointPermissionId,
                ]);

                return;
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SharePoint permission revocation failed permanently', [
            'document_id' => $this->documentId,
            'permission_id' => $this->sharepointPermissionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);
    }
}
