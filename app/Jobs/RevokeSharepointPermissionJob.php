<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\SharepointGraphService;
use App\Support\StagingProtection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

class RevokeSharepointPermissionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * Waiting for the document's lock releases the job, which counts as an attempt; `$maxExceptions` caps real failures.
     */
    public int $tries = 15;

    public int $maxExceptions = 3;

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
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [DocumentSharepointLock::for($this->documentId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (StagingProtection::sharepointIsReadOnly($this->sharepointSiteId, config('filesystems.sharepoint.archive_drive_id'))) {
            return;
        }

        // Republishing soon after a hide finds and reuses this same link; deleting it would break the published document.
        $document = Document::query()->find($this->documentId, ['id', 'status', 'removed_from_sharepoint_at', 'sharepoint_permission_id']);

        if ($document?->isPublished() && $document->sharepoint_permission_id === $this->sharepointPermissionId) {
            Log::info('Permission is in use by the republished document, skipping revocation', ['document_id' => $this->documentId]);

            return;
        }

        Log::info('Revoking SharePoint permission for document', [
            'document_id' => $this->documentId,
            'permission_id' => $this->sharepointPermissionId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $graph = $this->makeGraphService();

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
     * Extracted so tests can stub it.
     */
    protected function makeGraphService(): SharepointGraphService
    {
        return new SharepointGraphService(
            siteId: $this->sharepointSiteId,
            driveId: config('filesystems.sharepoint.archive_drive_id'),
            listId: $this->sharepointListId,
        );
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
            'attempts' => $this->attempts(),
        ]);
    }
}
