<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Institution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DocumentSharepointSyncService
{
    /**
     * Sync a document from SharePoint, updating metadata and permissions.
     *
     * @return Document|null Returns the document on success, null on non-fatal failure.
     *
     * @throws \InvalidArgumentException|\RuntimeException|\Exception On fatal errors.
     */
    public function sync(Document $document, bool $force = false): ?Document
    {
        $document->sync_status = 'syncing';
        $document->sync_attempts = ($document->sync_attempts ?? 0) + 1;
        $document->last_sync_attempt_at = Carbon::now();
        $document->sync_error_message = null;
        $document->save();

        try {
            $contentField = 'Turinys';
            $institutionField = 'Padalinys';

            $graph = new SharepointGraphService(
                siteId: $document->sharepoint_site_id,
                driveId: config('filesystems.sharepoint.archive_drive_id'),
                listId: $document->sharepoint_list_id
            );

            $additionalData = $graph->getListItem(
                $document->sharepoint_site_id,
                $document->sharepoint_list_id,
                $document->sharepoint_id
            )->getAdditionalData();

            $eTagMatches = $document->eTag === $additionalData['@odata.etag'];
            $hasInvalidUrl = str_contains($document->anonymous_url ?? '', ':f:');

            Log::info('Document sync eTag check', [
                'document_id' => $document->id,
                'etag_matches' => $eTagMatches,
                'url_masked' => $this->maskUrl($document->anonymous_url),
                'has_folder_url' => $hasInvalidUrl,
                'force' => $force,
                'will_skip_permission_check' => ! $force && $eTagMatches && ! $hasInvalidUrl,
            ]);

            if (! $force && $eTagMatches && ! $hasInvalidUrl) {
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

            $document->document_date = isset($additionalData['Date'])
                ? Carbon::parseFromLocale(time: $additionalData['Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius')
                : $document->document_date;
            $document->effective_date = isset($additionalData['Effective_x0020_Date'])
                ? Carbon::parseFromLocale(time: $additionalData['Effective_x0020_Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius')
                : $document->effective_date;
            $document->expiration_date = isset($additionalData['Expiration_x0020_Date0'])
                ? Carbon::parseFromLocale(time: $additionalData['Expiration_x0020_Date0'], timezone: 'UTC')->setTimezone('Europe/Vilnius')
                : $document->expiration_date;

            $document->name = $additionalData['Name'] ?? $document->name;
            $document->title = $additionalData['Title'] ?? $document->title;
            $document->eTag = $additionalData['@odata.etag'] ?? $document->eTag;
            $document->language = $additionalData['Language'] ?? $document->language;

            if (isset($additionalData[$institutionField]['Label'])) {
                $document->institution()->associate(
                    Institution::query()
                        ->where('name->lt', $additionalData[$institutionField]['Label'])
                        ->orWhere('short_name->lt', $additionalData[$institutionField]['Label'])
                        ->first()
                );
            }

            $document->content_type = $additionalData[$contentField]['Label'] ?? $document->content_type;
            $document->summary = $additionalData['Summary'] ?? $document->summary;

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

            $anonymousPermission = $graph->getDriveItemPublicLink($driveItem->getId());

            Log::info('Permission lookup result', [
                'document_id' => $document->id,
                'drive_item_id' => $driveItem->getId(),
                'permission_found' => $anonymousPermission !== null,
                'current_url_masked' => $this->maskUrl($document->anonymous_url),
            ]);

            if ($anonymousPermission === null) {
                Log::info('Creating new permission for document', [
                    'document_id' => $document->id,
                    'reason' => 'No valid permission found',
                ]);

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

                $document->anonymous_url = $newUrl;
                $document->sharepoint_permission_id = $anonymousPermission->getId();
            } else {
                $newUrl = $anonymousPermission->getLink()->getWebUrl();
                $urlChanged = $document->anonymous_url !== $newUrl;

                Log::info('Using existing permission', [
                    'document_id' => $document->id,
                    'url_changed' => $urlChanged,
                ]);

                if ($urlChanged) {
                    Log::warning('Permission URL changed', [
                        'document_id' => $document->id,
                        'had_previous_url' => ! empty($document->anonymous_url),
                    ]);
                }

                $document->anonymous_url = $newUrl;
                $document->sharepoint_permission_id = $anonymousPermission->getId();
            }

            $document->checked_at = Carbon::now();
            $document->sync_status = 'success';
            $document->save();
            $document->refresh();

            return $document;
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Cannot create public permission for folders')) {
                Log::warning('Document points to folder instead of file', [
                    'document_id' => $document->id,
                    'sharepoint_id' => $document->sharepoint_id,
                    'title' => $document->title,
                ]);

                $document->sync_status = 'failed';
                $document->sync_error_message = 'Document references a folder (folders not supported)';
                $document->save();

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

                $document->sync_status = 'failed';
                $document->sync_error_message = 'SharePoint item not found (may have been deleted)';
                $document->save();

                return null;
            }

            throw $e;
        } catch (\Exception $e) {
            Log::error('SharePoint document sync failed', [
                'document_id' => $document->id,
                'sharepoint_id' => $document->sharepoint_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sync_attempt' => $document->sync_attempts,
            ]);

            if ($document->anonymous_url && str_contains($e->getMessage(), 'createLink')) {
                Log::warning('Clearing stale anonymous_url due to permission creation failure', [
                    'document_id' => $document->id,
                    'had_url' => true,
                ]);
                $document->anonymous_url = null;
            }

            $document->sync_status = 'failed';
            $document->sync_error_message = $e->getMessage();
            $document->save();

            throw $e;
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
