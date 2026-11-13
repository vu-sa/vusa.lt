<?php

namespace App\Services\Documents;

use App\Jobs\RefreshDocumentThumbnailJob;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Services\SharepointGraphService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Generated\Models\DriveItem;

/**
 * First-page previews and short-lived open links for archive documents that knowledge items point at.
 * Both only read SharePoint and never create a sharing link, so hidden documents stay off public pages.
 */
class DocumentPreviewService
{
    private const string DIRECTORY = 'document-thumbnails';

    private const int MAX_BYTES = 5 * 1024 * 1024;

    public function needsThumbnail(Document $document): bool
    {
        return $document->thumbnail_etag !== $document->eTag;
    }

    /**
     * A never-synced document (hidden ones are not synced on a schedule) has no eTag to key a thumbnail
     * by, so it is synced first; that job queues the thumbnail.
     */
    public function queueThumbnail(Document $document): void
    {
        if ($document->eTag === null) {
            SyncDocumentFromSharePointJob::dispatch($document)->afterCommit();
        } elseif ($this->needsThumbnail($document)) {
            RefreshDocumentThumbnailJob::dispatch($document)->afterCommit();
        }
    }

    /**
     * Never throws: a failed fetch leaves the columns untouched, so the next run retries.
     */
    public function refreshThumbnail(Document $document): void
    {
        try {
            $url = (($this->driveItem($document)->getThumbnails() ?? [])[0] ?? null)?->getLarge()?->getUrl();
            $path = $url === null ? null : $this->storeThumbnail($document, $url);
        } catch (\Throwable $e) {
            Log::warning('Failed to refresh document thumbnail', ['document_id' => $document->id, 'error' => $e->getMessage()]);

            return;
        }

        if ($path === null && $document->thumbnail_path !== null) {
            Storage::delete($document->thumbnail_path);
        }

        $document->forceFill(['thumbnail_path' => $path, 'thumbnail_etag' => $document->eTag])->saveQuietly();
    }

    /**
     * SharePoint's in-browser viewer, falling back to a download link; both expire within the hour.
     */
    public function temporaryUrl(Document $document): ?string
    {
        try {
            $graph = $this->graphFor($document);
            $driveItem = $this->driveItem($document, $graph);
            $driveId = $driveItem->getParentReference()?->getDriveId();
            $previewUrl = $driveId !== null && $driveItem->getId() !== null ? $graph->getDriveItemPreviewUrl($driveId, $driveItem->getId()) : null;

            return $previewUrl ?? ($driveItem->getAdditionalData()['@microsoft.graph.downloadUrl'] ?? null);
        } catch (\Throwable $e) {
            Log::warning('Failed to get a temporary document URL', ['document_id' => $document->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function graphFor(Document $document): SharepointGraphService
    {
        return new SharepointGraphService(
            siteId: $document->sharepoint_site_id,
            driveId: config('filesystems.sharepoint.archive_drive_id'),
            listId: $document->sharepoint_list_id,
        );
    }

    private function driveItem(Document $document, ?SharepointGraphService $graph = null): DriveItem
    {
        return ($graph ?? $this->graphFor($document))->getDriveItemByListItem(
            $document->sharepoint_site_id,
            $document->sharepoint_list_id,
            $document->sharepoint_id,
        );
    }

    private function storeThumbnail(Document $document, string $url): string
    {
        $response = Http::timeout(15)->get($url)->throw();

        if (! str_starts_with((string) $response->header('Content-Type'), 'image/') || strlen($response->body()) > self::MAX_BYTES) {
            throw new \RuntimeException('SharePoint thumbnail is not an image or is too large');
        }

        $path = self::DIRECTORY.'/'.$document->id.'.webp';
        app(ThumbnailStore::class)->put($path, $response->body());

        return $path;
    }
}
