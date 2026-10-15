<?php

namespace App\Services;

use App\Helpers\InternetShortcutParser;
use App\Jobs\RevokeSharepointPermissionJob;
use App\Models\Document;
use App\Services\Documents\SharepointDocumentFields;
use App\Support\StagingProtection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\DriveItem;

class DocumentSharepointSyncService
{
    /**
     * Sync a document from SharePoint, updating metadata and permissions.
     *
     * @return Document|null Returns the document on success, null on non-fatal failure.
     *
     * @throws \Throwable On fatal errors.
     */
    public function sync(Document $document, bool $force = false): ?Document
    {
        $document->sync_status = 'syncing';
        $document->sync_attempts = ($document->sync_attempts ?? 0) + 1;
        $document->last_sync_attempt_at = Carbon::now();
        $document->sync_error_message = null;
        $document->save();

        try {
            $graph = $this->makeGraphService($document);

            $additionalData = $graph->getListItem(
                $document->sharepoint_site_id,
                $document->sharepoint_list_id,
                $document->sharepoint_id
            )->getAdditionalData();

            $eTagMatches = $document->eTag === $additionalData['@odata.etag'];
            $hasInvalidUrl = str_contains($document->anonymous_url ?? '', ':f:');
            // A `.url` shortcut whose real destination we never resolved still needs
            // a full pass, even when SharePoint says nothing else has changed.
            $needsShortcutTarget = $document->isUrlShortcut() && empty($document->link_url);
            // An unchanged file says nothing about a link that was never created.
            $needsLink = $document->isPublished() && empty($document->anonymous_url);

            Log::info('Document sync eTag check', [
                'document_id' => $document->id,
                'etag_matches' => $eTagMatches,
                'url_masked' => $this->maskUrl($document->anonymous_url),
                'has_folder_url' => $hasInvalidUrl,
                'needs_shortcut_target' => $needsShortcutTarget,
                'needs_link' => $needsLink,
                'force' => $force,
                'will_skip_permission_check' => ! $force && $eTagMatches && ! $hasInvalidUrl && ! $needsShortcutTarget && ! $needsLink,
            ]);

            if (! $force && $eTagMatches && ! $hasInvalidUrl && ! $needsShortcutTarget && ! $needsLink) {
                Log::info('SharePoint document was already up to date', ['document_id' => $document->id]);
                $document->checked_at = Carbon::now();
                $document->sync_status = 'success';
                $document->save();

                return null;
            }

            if ($hasInvalidUrl) {
                Log::warning('Document has folder URL - forcing full sync despite eTag match', [
                    'document_id' => $document->id,
                    'url_masked' => $this->maskUrl($document->anonymous_url),
                ]);
            }

            if ($needsShortcutTarget) {
                Log::info('Document is an unresolved .url shortcut - forcing full sync despite eTag match', [
                    'document_id' => $document->id,
                ]);
            }

            SharepointDocumentFields::apply($document, $additionalData);
            $document->name = $additionalData['Name'] ?? $document->name;
            $document->eTag = $additionalData['@odata.etag'] ?? $document->eTag;

            // Only a published document may carry a public link; the rest just track metadata.
            if (! $document->isPublished()) {
                $document->checked_at = Carbon::now();
                $document->sync_status = 'success';
                $document->save();

                return $document;
            }

            $driveItem = $graph->getDriveItemByListItem(
                $document->sharepoint_site_id,
                $document->sharepoint_list_id,
                $document->sharepoint_id
            );

            if ($driveItem->getFolder() !== null) {
                Log::error('SharePoint returned folder instead of file for list item', [
                    'document_id' => $document->id,
                    'list_item_id' => $document->sharepoint_id,
                    'drive_item_id' => $driveItem->getId(),
                    'drive_item_name' => $driveItem->getName(),
                ]);
                throw new \Exception("SharePoint returned folder '{$driveItem->getName()}' instead of file for list item {$document->sharepoint_id}");
            }

            $this->syncShortcutTarget($document, $graph, $driveItem);

            $anonymousPermission = $graph->getDriveItemPublicLink($driveItem->getId());
            $createdPermission = false;

            Log::info('Permission lookup result', [
                'document_id' => $document->id,
                'drive_item_id' => $driveItem->getId(),
                'permission_found' => $anonymousPermission !== null,
                'current_url_masked' => $this->maskUrl($document->anonymous_url),
            ]);

            if ($anonymousPermission === null) {
                if (StagingProtection::sharepointIsReadOnly(
                    $document->sharepoint_site_id,
                    config('filesystems.sharepoint.archive_drive_id'),
                )) {
                    Log::info('No public permission found; staging read-only mode prevents creating one', [
                        'document_id' => $document->id,
                    ]);

                    $document->checked_at = Carbon::now();
                    $document->sync_status = 'success';
                    $document->save();
                    $document->refresh();

                    return $document;
                }

                Log::info('Creating new permission for document', [
                    'document_id' => $document->id,
                    'reason' => 'No valid permission found',
                ]);

                $createdPermission = true;
                $anonymousPermission = $graph->createPublicPermission(
                    siteId: $document->sharepoint_site_id,
                    driveItemId: $driveItem->getId(),
                    datetime: false
                );

                $newUrl = $anonymousPermission->getLink()->getWebUrl();
                Log::info('New permission created', [
                    'document_id' => $document->id,
                    'url_changed' => $document->anonymous_url !== $newUrl,
                ]);
            } else {
                $newUrl = $anonymousPermission->getLink()->getWebUrl();

                Log::info('Using existing permission', [
                    'document_id' => $document->id,
                    'url_changed' => $document->anonymous_url !== $newUrl,
                ]);

                if ($document->anonymous_url !== $newUrl) {
                    Log::warning('Permission URL changed', [
                        'document_id' => $document->id,
                        'had_previous_url' => ! empty($document->anonymous_url),
                    ]);
                }
            }

            $permissionId = $anonymousPermission->getId();

            // The network calls above take a while; a manager may have hidden the document meanwhile.
            // Re-check under a row lock, so a hide either lands before this save or sees its permission.
            // The link only reaches the model here, so no other save can persist a link that was refused.
            $stillPublished = DB::transaction(function () use ($document, $newUrl, $permissionId): bool {
                $current = Document::query()->whereKey($document->getKey())->lockForUpdate()->first(['id', 'status', 'removed_from_sharepoint_at']);

                if ($current === null || ! $current->isPublished()) {
                    return false;
                }

                $document->anonymous_url = $newUrl;
                $document->sharepoint_permission_id = $permissionId;
                $document->checked_at = Carbon::now();
                $document->sync_status = 'success';
                $document->save();

                return true;
            });

            if (! $stillPublished) {
                Log::warning('Document was hidden while its public link was being prepared; not keeping the link', [
                    'document_id' => $document->id,
                    'revoking_new_link' => $createdPermission,
                ]);

                $document->refresh();

                if ($createdPermission) {
                    $this->deleteRefusedPermission($document, $graph, $driveItem->getId(), $permissionId);
                }

                $document->checked_at = Carbon::now();
                $document->sync_status = 'success';
                $document->save();

                return $document;
            }

            $document->refresh();

            return $document;
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Cannot create public permission for folders')) {
                Log::warning('Document points to folder instead of file', [
                    'document_id' => $document->id,
                    'sharepoint_id' => $document->sharepoint_id,
                    'title' => $document->title,
                ]);

                $this->markFailed($document, 'Document references a folder (folders not supported)');

                return null;
            }

            throw $e;
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'not found or inaccessible')) {
                Log::warning('Document references missing SharePoint item', [
                    'document_id' => $document->id,
                    'sharepoint_id' => $document->sharepoint_id,
                    'title' => $document->title,
                ]);

                $this->markFailed($document, 'SharePoint item not found (may have been deleted)');

                return null;
            }

            throw $e;
        } catch (\Throwable $e) {
            Log::error('SharePoint document sync failed', [
                'document_id' => $document->id,
                'sharepoint_id' => $document->sharepoint_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sync_attempt' => $document->sync_attempts,
            ]);

            $this->markFailed($document, $e->getMessage(), clearLink: str_contains($e->getMessage(), 'createLink'));

            throw $e;
        }
    }

    /**
     * Record a failure on the stored row, not on `$document`: the failed pass may have left metadata
     * or a link on it that must not be saved.
     */
    private function markFailed(Document $document, string $message, bool $clearLink = false): void
    {
        $stored = $document->fresh();

        if ($stored === null) {
            return;
        }

        $document->setRawAttributes($stored->getAttributes(), sync: true);

        if ($clearLink && $document->anonymous_url) {
            Log::warning('Clearing stale anonymous_url due to permission creation failure', ['document_id' => $document->id]);
            $document->anonymous_url = null;
        }

        $document->sync_status = 'failed';
        $document->sync_error_message = $message;
        $document->save();
    }

    /**
     * A link created for a document hidden meanwhile must not survive; a failed delete is retried by the queue.
     */
    private function deleteRefusedPermission(Document $document, SharepointGraphService $graph, string $driveItemId, string $permissionId): void
    {
        try {
            $graph->deletePermission($driveItemId, $permissionId);
        } catch (\Throwable $e) {
            Log::warning('Could not delete a refused public link; queueing its revocation', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            RevokeSharepointPermissionJob::dispatch(
                sharepointSiteId: $document->sharepoint_site_id,
                sharepointListId: $document->sharepoint_list_id,
                sharepointId: $document->sharepoint_id,
                sharepointPermissionId: $permissionId,
                documentId: $document->id,
            );
        }
    }

    /**
     * Build the Graph service for a document. Extracted so tests can stub it.
     */
    protected function makeGraphService(Document $document): SharepointGraphService
    {
        return new SharepointGraphService(
            siteId: $document->sharepoint_site_id,
            driveId: config('filesystems.sharepoint.archive_drive_id'),
            listId: $document->sharepoint_list_id
        );
    }

    /**
     * Resolve the real destination of a SharePoint `.url` internet shortcut.
     *
     * Never throws: an unresolved shortcut simply degrades to the
     * SharePoint viewer link, which is the pre-existing behavior. A
     * permanently unparseable shortcut will re-attempt resolution on every
     * sync cycle (link_url stays null), which is acceptable at the current
     * scale of a handful of shortcut documents.
     */
    private function syncShortcutTarget(Document $document, SharepointGraphService $graph, DriveItem $driveItem): void
    {
        if (! $document->isUrlShortcut()) {
            // The list item may have been replaced by a real document upstream.
            $document->link_url = null;

            return;
        }

        try {
            $target = InternetShortcutParser::parse(
                $graph->getDriveItemContent($driveItem->getId())
            );

            if ($target === null) {
                Log::warning('Could not resolve .url shortcut target', [
                    'document_id' => $document->id,
                    'drive_item_id' => $driveItem->getId(),
                ]);

                // Keep a previously resolved target rather than dropping a working link.
                return;
            }

            $document->link_url = $target;
        } catch (\Throwable $e) {
            Log::warning('Shortcut target resolution failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mask a SharePoint URL for safe logging.
     */
    private function maskUrl(?string $url): string
    {
        if (empty($url)) {
            return 'none';
        }

        if (preg_match('/[?&]r=([^&]+)/', $url, $matches)) {
            return substr($matches[1], 0, 8).'...';
        }

        return 'masked';
    }
}
