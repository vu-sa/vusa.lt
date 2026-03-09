<?php

namespace App\Services;

use App\Enums\SharepointConfigEnum;
use App\Enums\SharepointFieldEnum;
use App\Enums\SharepointPermissionTypeEnum;
use App\Enums\SharepointScopeEnum;
use App\Models\Document;
use App\Models\Institution;
use App\Models\SharepointFile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchRequestItem;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\Children\ChildrenRequestBuilder;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\CreateLink\CreateLinkPostRequestBody;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\DriveItemItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Models\FieldValueSet;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Models\PermissionCollectionResponse;
use Microsoft\Graph\Generated\Sites\Item\Lists\Item\Items\Item\DriveItem\DriveItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Sites\Item\Lists\Item\Items\Item\Fields\FieldsRequestBuilderPatchRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * SharepointGraphService
 *
 * This class is used to interact with Sharepoint API
 * It has common methods for all Sharepoint API calls in a specific drive
 */
class SharepointGraphService
{
    protected GraphServiceClient $graph;

    protected string $graphApiBaseUrl;

    public string $siteId;

    protected string $driveId;

    protected $listId;

    /**
     * Default number of days for SharePoint permission expiry
     */
    private const DEFAULT_PERMISSION_EXPIRY_DAYS = 365;

    /**
     * SharePoint Graph API Service
     *
     * This service uses technical constants from SharepointConfigEnum for API URLs,
     * retry logic, timeouts, and other static configuration values.
     *
     * @see \App\Enums\SharepointConfigEnum For static technical configuration
     *
     * Set for which sharepoint site and drive to interact with
     * If no siteId or driveId is provided, it will use the default values from config
     *
     * @return void
     */
    public function __construct(?string $siteId = null, ?string $driveId = null, ?string $listId = null)
    {
        try {
            $tokenRequestContext = new ClientCredentialContext(
                config('filesystems.sharepoint.tenant_id'),
                config('filesystems.sharepoint.client_id'),
                config('filesystems.sharepoint.client_secret')
            );

            $this->graph = new GraphServiceClient($tokenRequestContext);
            $this->graphApiBaseUrl = SharepointConfigEnum::API_BASE_URL()->label;

            $this->siteId = $siteId ?? config('filesystems.sharepoint.site_id');
            $this->driveId = $driveId ?? $this->getDrive()->getId();
            $this->listId = $listId;

            $this->logInfo('SharepointGraphService initialized', [
                'site_id' => $this->siteId,
                'drive_id' => $this->driveId,
                'list_id' => $this->listId,
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to initialize SharepointGraphService', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function getDrive(): Models\Drive
    {
        // This doesn't work for sharepoint archive folder
        $drive = $this->graph->sites()->bySiteId($this->siteId)->drive()->get()->wait();

        return $drive;
    }

    /**
     * getDriveItemByPath
     *
     * Note: for some reason DriveItems are not returned
     *
     * @return Collection
     */
    public function getDriveItemByPath(string $path, bool $getChildren = false)
    {
        // encode path
        $childrenPath = $getChildren ? ':/children' : '';

        try {
            $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}{$childrenPath}?\$expand=listItem,thumbnails";

            $drive = $this->graph->drives()->byDriveId($this->driveId)->withUrl($sharepointPathFinal)->get()->wait();

        } catch (ODataError $e) {
            return collect([]);
        }

        $driveItems = collect($drive->getAdditionalData()['value']);

        return $this->parseDriveItems($driveItems);
    }

    /**
     * Get a DriveItem object by path (not parsed into collection).
     * Returns the actual Microsoft Graph DriveItem model.
     */
    public function getDriveItemObjectByPath(string $path): ?Models\DriveItem
    {
        try {
            $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}?\$expand=listItem";

            $driveItem = $this->graph->drives()
                ->byDriveId($this->driveId)
                ->root()
                ->withUrl($sharepointPathFinal)
                ->get()
                ->wait();

            return $driveItem;
        } catch (ODataError $e) {
            Log::warning('Failed to get drive item by path', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to get drive item by path', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /* public function getDriveItemByIdWithListItem(string $driveItemId): Models\DriveItem */
    /* { */
    /*    $requestConfiguration = new DriveItemItemRequestBuilderGetRequestConfiguration(); */
    /*    $queryParameters = DriveItemItemRequestBuilderGetRequestConfiguration::createQueryParameters(); */
    /**/
    /*    $queryParameters->expand = ['listItem']; */
    /**/
    /*    $requestConfiguration->queryParameters = $queryParameters; */
    /**/
    /*    $driveItem = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->get($requestConfiguration)->wait(); */
    /**/
    /*    return $driveItem; */
    /* } */

    /**
     * Get a drive item by its ID with list item metadata.
     */
    public function getDriveItemById(string $driveItemId): ?Models\DriveItem
    {
        try {
            $requestConfiguration = new DriveItemItemRequestBuilderGetRequestConfiguration;
            $queryParameters = DriveItemItemRequestBuilderGetRequestConfiguration::createQueryParameters();

            $queryParameters->expand = ['listItem'];

            $requestConfiguration->queryParameters = $queryParameters;

            $driveItem = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->get($requestConfiguration)->wait();

            return $driveItem;
        } catch (\Exception $e) {
            \Log::warning('Failed to get drive item by ID', [
                'driveItemId' => $driveItemId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getDriveItemByListItem(string $siteId, string $listId, string $listItemId): Models\DriveItem
    {
        $requestConfiguration = new DriveItemRequestBuilderGetRequestConfiguration;
        $queryParameters = DriveItemRequestBuilderGetRequestConfiguration::createQueryParameters();

        $queryParameters->expand = ['thumbnails'];

        $requestConfiguration->queryParameters = $queryParameters;

        $driveItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->driveItem()->get($requestConfiguration)->wait();

        return $driveItem;
    }

    public function updateDriveItemByPath(string $path, array $fields): ?Models\DriveItem
    {
        try {
            $path = rawurlencode($path);

            $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}";

            $updatableDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->get()->wait();

            isset($fields['name']) ? $updatableDriveItem->setName($fields['name']) : null;

            $result = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($updatableDriveItem->getId())->patch($updatableDriveItem)->wait();

            return $result;
        } catch (ODataError $e) {
            $this->logError('Failed to update drive item by path', [
                'path' => $path,
                'fields' => $fields,
                'error' => $e->getMessage(),
                'error_code' => $e->getError()?->getCode(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logError('Unexpected error updating drive item by path', [
                'path' => $path,
                'fields' => $fields,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getListItem(string $siteId, string $listId, string $listItemId): Models\FieldValueSet
    {
        try {
            $listItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->fields()->get()->wait();

            return $listItem;
        } catch (\Microsoft\Graph\Generated\Models\ODataErrors\ODataError $e) {
            // List item doesn't exist (404) or access denied (403)
            $this->logWarning('SharePoint list item not found or inaccessible', [
                'site_id' => $siteId,
                'list_id' => $listId,
                'list_item_id' => $listItemId,
                'error_message' => $e->getMessage() ?: 'Item not found',
            ]);

            throw new \RuntimeException("SharePoint list item {$listItemId} not found or inaccessible");
        }
    }

    public function updateListItem(string $listId, string $listItemId, array $fields): Models\FieldValueSet
    {
        try {
            $requestConfiguration = new FieldsRequestBuilderPatchRequestConfiguration;
            $fieldValueSet = new FieldValueSet;

            $fieldValueSet->setAdditionalData($fields);

            $updatedListItem = $this->graph->sites()->bySiteId($this->siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->fields()->patch($fieldValueSet, $requestConfiguration)->wait();

            return $updatedListItem;
        } catch (ODataError $e) {
            $this->logError('Failed to update SharePoint list item', [
                'list_id' => $listId,
                'list_item_id' => $listItemId,
                'fields' => $fields,
                'error_message' => $e->getMessage() ?: 'Unknown error',
                'error_code' => $e->getError()?->getCode(),
                'error_details' => $e->getError()?->getMessage(),
            ]);

            throw $e;
        }
    }

    // Since every institution can have types and with them associated documents, we need to
    // get them by batch
    public function getDriveItemsChildrenByPaths(array $paths)
    {
        $pathCollection = collect($paths);

        $batch = new BatchRequestContent(
            $pathCollection->map(function ($path) {
                $path = rawurlencode($path);

                $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}:/children?\expand=listItem,thumbnails";

                $request = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->toGetRequestInformation();

                return new BatchRequestItem($request);
            })->toArray()
        );

        // Create a batch request builder to send the batched requests
        $batchRequestBuilder = new BatchRequestBuilder($this->graph->getRequestAdapter());

        $batchResponse = $batchRequestBuilder->postAsync($batch)->wait();

        $driveItemCollections = collect($batch->getRequests())->map(function (BatchRequestItem $request) use ($batchResponse) {
            $response = $batchResponse->getResponseBody($request->getId(), Models\DriveItemCollectionResponse::class)->getValue();

            return $response;
        });

        $driveItems = $driveItemCollections->map(function (?array $driveItemCollection) {
            if (! $driveItemCollection) {
                return false;
            }

            // flatten driveItemCollection
            return $driveItemCollection;
        })->reject(fn ($value) => $value === false)->flatten()->map(function (Models\DriveItem $driveItem) {
            // turn to simple array
            return $driveItem->getBackingStore()->enumerate();
        });

        return $this->parseDriveItems($driveItems);
    }

    protected function getDriveItemPermissions(string $driveItemId): PermissionCollectionResponse
    {
        $permissions = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->permissions()->get()->wait();

        return $permissions;
    }

    public function getDriveItemPublicLink(string $driveItemId): ?Models\Permission
    {
        $permissions = collect($this->getDriveItemPermissions($driveItemId)->getValue());

        $permission = $permissions->filter(function (Models\Permission $permission) {
            // Filter criteria:
            // 1. Must have a link (not SharePoint group permission)
            // 2. Must be anonymous scope
            // 3. Must NOT have expiration (our standard)
            // 4. CRITICAL: Must NOT be inherited from parent folder
            // 5. CRITICAL: Must be a file URL (:b: or :w:), not a folder URL (:f:)
            if (! $permission->getLink()
                || $permission->getLink()->getScope() !== 'anonymous'
                || $permission->getExpirationDateTime() !== null
                || $permission->getInheritedFrom() !== null) {
                return false;
            }

            // Check URL type - must be file (:b: or :w:), not folder (:f:)
            $url = $permission->getLink()->getWebUrl();
            if (str_contains($url, ':f:')) {
                $this->logWarning('Rejecting folder URL permission on file', [
                    'drive_item_id' => $permission->getId(),
                    'url_type' => 'folder',
                ]);

                return false;
            }

            return true;
        })->first();

        if ($permission) {
            $this->logInfo('Found existing public link', [
                'drive_item_id' => $driveItemId,
                'permission_id' => $permission->getId(),
                'url_masked' => $this->maskUrl($permission->getLink()->getWebUrl()),
            ]);
        } else {
            $this->logInfo('No direct public link found', [
                'drive_item_id' => $driveItemId,
                'total_permissions' => $permissions->count(),
            ]);
        }

        return $permission ?? null;
    }

    public function createPublicPermission(?string $siteId, string $driveItemId, Carbon|false|null $datetime = null): Models\Permission
    {
        $this->validateNotEmpty(['driveItemId' => $driveItemId]);

        // Validate item is a file, not folder
        $driveItem = $this->validateItemIsFile($driveItemId);

        return $this->executeWithRetry(function () use ($siteId, $driveItemId, $datetime, $driveItem) {
            $siteId = $siteId ?? $this->siteId;
            $datetime = $datetime ?? Carbon::now()->addDays(self::DEFAULT_PERMISSION_EXPIRY_DAYS);

            $requestBody = new CreateLinkPostRequestBody;
            $requestBody->setType(SharepointPermissionTypeEnum::VIEW()->label);
            $requestBody->setScope(SharepointScopeEnum::ANONYMOUS()->label);

            if ($datetime !== false) {
                $requestBody->setExpirationDateTime($datetime);
            }

            $sharepointPathFinal = "{$this->graphApiBaseUrl}sites/{$siteId}/drive/items/{$driveItemId}/createLink";

            // Use driveId (not driveItemId) for byDriveId, then driveItemId for byDriveItemId
            $permission = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->createLink()->withUrl($sharepointPathFinal)->post($requestBody)->wait();

            // Enhanced logging
            $this->logInfo('Public permission created', [
                'drive_item_id' => $driveItemId,
                'drive_item_name' => $driveItem->getName(),
                'drive_item_size' => $driveItem->getSize(),
                'permission_id' => $permission->getId(),
                'url_masked' => $this->maskUrl($permission->getLink()->getWebUrl()),
                'permission_scope' => $permission->getLink()->getScope(),
                'expiration' => ($datetime instanceof \Carbon\Carbon) ? $datetime->toDateTimeString() : 'never',
                'user_id' => auth()->id() ?? 'system',
            ]);

            return $permission;
        }, 'createPublicPermission');
    }

    /**
     * Delete a permission from a drive item
     *
     * @param  string  $driveItemId  The drive item ID
     * @param  string  $permissionId  The permission ID to delete
     */
    public function deletePermission(string $driveItemId, string $permissionId): void
    {
        $this->graph->drives()
            ->byDriveId($this->driveId)
            ->items()
            ->byDriveItemId($driveItemId)
            ->permissions()
            ->byPermissionId($permissionId)
            ->delete()
            ->wait();

        $this->logInfo('Permission deleted', [
            'drive_item_id' => $driveItemId,
            'permission_id' => $permissionId,
        ]);
    }

    public function uploadDriveItem(string $filePath, UploadedFile $file): Models\DriveItem
    {
        $factory = new Psr17Factory;

        $stream = $factory->createStreamFromFile($file->getPathname(), 'r');

        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$filePath}:/content?\$expand=listItem&@microsoft.graph.conflictBehavior=rename";

        $uploadedDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->content()->put($stream)->wait();

        return $uploadedDriveItem;
    }

    public function deleteDriveItem(string $driveItemId): void
    {
        $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->delete()->wait();
    }

    /**
     * Create a folder in SharePoint.
     * Creates parent folders recursively if they don't exist.
     *
     * @param  string  $folderPath  The full path for the folder (e.g., "General/Padaliniai/NewFolder")
     */
    public function createFolder(string $folderPath): Models\DriveItem
    {
        $pathParts = explode('/', trim($folderPath, '/'));
        $folderName = array_pop($pathParts);
        $parentPath = implode('/', $pathParts);

        // Build the parent item path
        $parentUrl = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root';
        if (! empty($parentPath)) {
            $parentUrl .= ':/'.$parentPath.':';
        }

        $requestBody = new Models\DriveItem;
        $requestBody->setName($folderName);
        $requestBody->setFolder(new Models\Folder);
        $requestBody->setAdditionalData([
            '@microsoft.graph.conflictBehavior' => 'fail',
        ]);

        $childrenRequestBuilder = new ChildrenRequestBuilder(
            $parentUrl.'/children',
            $this->graph->getRequestAdapter()
        );

        $createdFolder = $childrenRequestBuilder->post($requestBody)->wait();

        $this->logInfo('Folder created', [
            'path' => $folderPath,
            'folder_id' => $createdFolder->getId(),
        ]);

        return $createdFolder;
    }

    /**
     * Upload a .url shortcut file to SharePoint.
     * Creates necessary parent folders if they don't exist.
     *
     * @param  string  $filePath  The full path including filename (e.g., "Folder/Subfolder/shortcut.url")
     * @param  string  $content  The .url file content
     */
    public function uploadUrlShortcut(string $filePath, string $content): Models\DriveItem
    {
        $factory = new Psr17Factory;

        $stream = $factory->createStream($content);

        // Use fail conflict behavior since we don't want to overwrite existing shortcuts
        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$filePath}:/content?\$expand=listItem&@microsoft.graph.conflictBehavior=fail";

        $uploadedDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->content()->put($stream)->wait();

        return $uploadedDriveItem;
    }

    /**
     * parseDriveItems
     *
     * @return Collection
     */
    private function parseDriveItems(Collection $driveItems)
    {
        // get all driveitem ids
        $driveItemIds = $driveItems->map(function ($driveItem) {
            return $driveItem['id'];
        })->toArray();

        // load all sharepointFile models wherein sharepointfile.sharepoint_id
        // is in $driveItemIds
        $sharepointFiles = SharepointFile::whereIn('sharepoint_id', $driveItemIds)->with('fileables.fileable', 'comments')->get();

        $parsedDriveItems = $driveItems->map(function (array $driveItem) use ($sharepointFiles) {
            return [
                'id' => $driveItem['id'],
                'sharepointFile' => $sharepointFiles->filter(function ($sharepointFile) use ($driveItem) {
                    return $sharepointFile->sharepoint_id == $driveItem['id'];
                })->first(),
                'name' => $driveItem['name'],
                'file' => $driveItem['file'] ?? null,
                // if driveitem is a file, get content
                'folder' => $driveItem['folder'] ?? null,
                'size' => $driveItem['size'],
                'createdDateTime' => $driveItem['createdDateTime'],
                'lastModifiedDateTime' => $driveItem['lastModifiedDateTime'],
                'webUrl' => $driveItem['webUrl'],
                'listItem' => [
                    'fields' => $driveItem['listItem']['fields'] ?? null,
                ],
                'permissions' => $driveItem['permissions'] ?? null,
                'thumbnails' => collect($driveItem['thumbnails'] ?? [])->map(function ($thumbnail) {
                    return [
                        'large' => [
                            'url' => $thumbnail['large']['url'],
                        ],
                    ];
                }),
            ];
        });

        return $parsedDriveItems;
    }

    /**
     * Batch process documents from SharePoint
     *
     * @param  EloquentCollection<int, Document>  $documentCollection
     */
    public function batchProcessDocuments(EloquentCollection $documentCollection)
    {

        // filter by documents that don't exist
        $documentColection = $documentCollection->filter(function (Document $document): bool {
            return Document::query()->where('sharepoint_id', $document->sharepoint_id)->doesntExist();
        });

        // If no documents to process, return
        if ($documentColection->isEmpty()) {
            return $documentColection;
        }

        // First, get the drive item and associated data
        $batch = new BatchRequestContent(
            $documentColection->map(function (Document $document): BatchRequestItem {

                $requestConfiguration = new DriveItemRequestBuilderGetRequestConfiguration;
                $queryParameters = DriveItemRequestBuilderGetRequestConfiguration::createQueryParameters();

                $queryParameters->expand = ['listItem', 'permissions'];

                $requestConfiguration->queryParameters = $queryParameters;

                $driveItemRequestConfiguration = $this->graph->sites()->bySiteId($document->sharepoint_site_id)->lists()->byListId($document->sharepoint_list_id)->items()->byListItemId($document->sharepoint_id)->driveItem()->toGetRequestInformation($requestConfiguration);

                return new BatchRequestItem($driveItemRequestConfiguration, $document->sharepoint_id);
            })->toArray()
        );

        // Create a batch request builder to send the batched requests
        $batchRequestBuilder = new BatchRequestBuilder($this->graph->getRequestAdapter());

        $batchResponse = $batchRequestBuilder->postAsync($batch)->wait();

        $driveItemCollections = collect($batch->getRequests())->map(function (BatchRequestItem $request) use ($batchResponse) {
            $additionalData = $batchResponse->getResponseBody($request->getId(), Models\DriveItemCollectionResponse::class)->getAdditionalData();

            $additionalData['listItem']['uniqueId'] = $request->getId();

            return $additionalData;
            // keyBy list item id
        })->keyBy(fn ($value) => $value['listItem']['uniqueId']);

        // Filter out folders - only process files
        $folderItems = $driveItemCollections->filter(fn ($item) => isset($item['folder']));
        if ($folderItems->isNotEmpty()) {
            Log::warning('Batch processing encountered folders instead of files', [
                'folder_count' => $folderItems->count(),
                'folder_ids' => $folderItems->keys()->toArray(),
            ]);
            $driveItemCollections = $driveItemCollections->reject(fn ($item) => isset($item['folder']));
        }

        // Get permissions without anonymous url
        $driveItemsWithoutAnonymousUrl = $driveItemCollections->filter(function ($driveItem) {
            return collect($driveItem['permissions'])->contains(function ($permission) {
                $hasAnonymous = isset($permission['link']['scope']) ? $permission['link']['scope'] === SharepointScopeEnum::ANONYMOUS()->label : false;

                $hasPassword = isset($permission['hasPassword']) ? $permission['hasPassword'] : false;

                return ! $hasAnonymous || $hasPassword;
            });
        });

        // Add anonymous url to drive items without it
        if ($driveItemsWithoutAnonymousUrl->isNotEmpty()) {
            $batch = new BatchRequestContent(
                $driveItemsWithoutAnonymousUrl->map(function (array $driveItem) {

                    $requestBody = new CreateLinkPostRequestBody;

                    $requestBody->setType(SharepointPermissionTypeEnum::VIEW()->label);
                    $requestBody->setScope(SharepointScopeEnum::ANONYMOUS()->label);

                    $sharepointPathFinal = "{$this->graphApiBaseUrl}sites/{$this->siteId}/drive/items/{$driveItem['id']}/createLink";

                    // This is the wrong chain, but it should work
                    $permissionRequestConfiguration = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItem['id'])->createLink()->withUrl($sharepointPathFinal)->toPostRequestInformation($requestBody);

                    return new BatchRequestItem($permissionRequestConfiguration, $driveItem['listItem']['uniqueId']);
                })->toArray()

            );
            $batchRequestBuilder = new BatchRequestBuilder($this->graph->getRequestAdapter());

            $batchResponse = $batchRequestBuilder->postAsync($batch)->wait();

            $permissionCollection = collect($batch->getRequests())->map(function (BatchRequestItem $request) use ($batchResponse) {
                $additionalData = $batchResponse->getResponseBody($request->getId(), Models\PermissionCollectionResponse::class)->getAdditionalData();

                $additionalData['list_item_unique_id'] = $request->getId();

                return $additionalData;
            })->keyBy(fn ($value) => $value['list_item_unique_id']);
        } else {
            $permissionCollection = collect([]);
        }

        $driveItemCollections->each(function ($driveItem, string $key) use ($permissionCollection, $driveItemCollections) {
            // get permission where collection key matches with driveitem collection key
            $permission = $permissionCollection->get($key);

            // add permission to drive item
            $driveItem['permissions'][] = $permission;

            // update drive item collection
            $driveItemCollections->put($key, $driveItem);
        });

        // Update documents
        $documentColection->each(function (Document $document) use ($driveItemCollections): void {
            $driveItem = $driveItemCollections->get($document->sharepoint_id);

            // Handle filtered folders - mark as failed
            if ($driveItem === null) {
                Log::warning('Document references a folder, skipping', [
                    'sharepoint_id' => $document->sharepoint_id,
                    'title' => $document->title,
                ]);
                $document->sync_status = 'failed';
                $document->sync_error_message = 'Document references a folder (folders not supported)';
                $document->save();

                return;
            }

            $document->name = $driveItem['name'];
            $document->title = isset($driveItem['listItem']['fields'][SharepointFieldEnum::TITLE()->label]) ? $driveItem['listItem']['fields'][SharepointFieldEnum::TITLE()->label] : $driveItem['name'];
            $document->eTag = $driveItem['listItem']['eTag'];
            $document->document_date = isset($driveItem['listItem']['fields'][SharepointFieldEnum::DATE()->label]) ? Carbon::parseFromLocale(time: $driveItem['listItem']['fields'][SharepointFieldEnum::DATE()->label], timezone: 'UTC')->setTimezone('Europe/Vilnius') : null;
            $document->effective_date = isset($driveItem['listItem']['fields'][SharepointFieldEnum::EFFECTIVE_DATE()->label]) ? Carbon::parseFromLocale(time: $driveItem['listItem']['fields'][SharepointFieldEnum::EFFECTIVE_DATE()->label], timezone: 'UTC')->setTimezone('Europe/Vilnius') : null;
            $document->expiration_date = isset($driveItem['listItem']['fields'][SharepointFieldEnum::EXPIRATION_DATE()->label]) ? Carbon::parseFromLocale(time: $driveItem['listItem']['fields'][SharepointFieldEnum::EXPIRATION_DATE()->label], timezone: 'UTC')->setTimezone('Europe/Vilnius') : null;

            $document->language = isset($driveItem['listItem']['fields'][SharepointFieldEnum::LANGUAGE()->label]) ? $driveItem['listItem']['fields'][SharepointFieldEnum::LANGUAGE()->label] : null;
            $document->content_type = isset($driveItem['listItem']['fields'][SharepointFieldEnum::TURINYS()->label]['Label']) ? $driveItem['listItem']['fields'][SharepointFieldEnum::TURINYS()->label]['Label'] : null;

            $document->summary = $driveItem['listItem']['fields'][SharepointFieldEnum::SUMMARY()->label] ?? null;
            /* $document->thumbnail_url = $driveItem['thumbnails'][0]['large']['url']; */

            $anonymousPermission = collect($driveItem['permissions'])->filter(function ($permission) {

                $isAnonymous = isset($permission['link']['scope']) ? $permission['link']['scope'] === SharepointScopeEnum::ANONYMOUS()->label : false;
                $hasPassword = isset($permission['hasPassword']) ? $permission['hasPassword'] : false;

                return $isAnonymous && ! $hasPassword;
            })->first();

            $document->anonymous_url = $anonymousPermission['link']['webUrl'] ?? null;
            $document->sharepoint_permission_id = $anonymousPermission['id'] ?? null;

            $document->checked_at = Carbon::now();

            $institutionFieldName = SharepointFieldEnum::PADALINYS()->label;

            if (isset($driveItem['listItem']['fields'][$institutionFieldName]['Label'])) {
                $document->institution()->associate(Institution::query()->where('name->lt', $driveItem['listItem']['fields'][$institutionFieldName]['Label'])->orWhere('short_name->lt', $driveItem['listItem']['fields'][$institutionFieldName]['Label'])->first());
            }

            $document->save();
        });

        return $documentColection;

    }

    /**
     * Log info message if logging is enabled
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log error message if logging is enabled
     */
    private function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * Log warning message if logging is enabled
     */
    private function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * Mask sensitive parts of SharePoint URL for safe logging
     * Returns format: "https://...sharepoint.com/:b:/.../Es4i...Fy-cg" (first/last 4 chars of file ID)
     *
     * SharePoint anonymous links are bearer tokens - anyone with the URL can access the file.
     * This method masks the unique file identifier to prevent URL leakage in logs.
     */
    private function maskUrl(string $url): string
    {
        // Extract the unique file identifier (last segment after last /)
        $segments = explode('/', $url);
        $fileId = end($segments);

        if (strlen($fileId) > 12) {
            $masked = substr($fileId, 0, 4).'...'.substr($fileId, -4);
            $segments[count($segments) - 1] = $masked;

            return implode('/', $segments);
        }

        return 'masked'; // Fallback if format unexpected
    }

    /**
     * Execute operation with retry logic
     */
    private function executeWithRetry(callable $operation, string $operationName, ?int $maxRetries = null): mixed
    {
        $maxRetries = $maxRetries ?? (int) SharepointConfigEnum::MAX_RETRIES()->label;
        $attempt = 1;

        while ($attempt <= $maxRetries + 1) {
            try {
                $result = $operation();

                if ($attempt > 1) {
                    $this->logInfo('Operation succeeded after retry', [
                        'operation' => $operationName,
                        'attempt' => $attempt,
                    ]);
                }

                return $result;
            } catch (\Exception $e) {
                // Extract more details from the exception
                $errorMessage = $e->getMessage();
                $errorDetails = [];

                // Try to get more details from Microsoft Graph exceptions
                if (method_exists($e, 'getResponse')) {
                    $response = $e->getResponse();
                    if ($response) {
                        $errorDetails['status_code'] = $response->getStatusCode();
                        $errorDetails['body'] = (string) $response->getBody();
                    }
                }

                // Check for ODataError which has more details
                if ($e instanceof \Microsoft\Graph\Generated\Models\ODataErrors\ODataError) {
                    $errorDetails['odata_error'] = $e->getError()?->getMessage() ?? 'No OData error message';
                    $errorDetails['odata_code'] = $e->getError()?->getCode() ?? 'No code';
                }

                // Log the exception class for debugging
                $errorDetails['exception_class'] = get_class($e);

                if ($attempt > $maxRetries) {
                    $this->logError('Operation failed after all retries', [
                        'operation' => $operationName,
                        'attempts' => $attempt,
                        'error' => $errorMessage ?: 'Empty message',
                        'details' => $errorDetails,
                    ]);
                    throw $e;
                }

                $this->logInfo('Operation failed, retrying', [
                    'operation' => $operationName,
                    'attempt' => $attempt,
                    'error' => $errorMessage ?: 'Empty message',
                    'details' => $errorDetails,
                ]);

                // Exponential backoff
                $delay = (int) SharepointConfigEnum::RETRY_DELAY_MS()->label * pow(2, $attempt - 1);
                \Illuminate\Support\Sleep::for($delay)->milliseconds();

                $attempt++;
            }
        }

        throw new \RuntimeException('Should not reach here');
    }

    /**
     * Validate required parameters
     */
    private function validateNotEmpty(array $params): void
    {
        foreach ($params as $name => $value) {
            if (empty($value)) {
                throw new \InvalidArgumentException("Parameter '{$name}' cannot be empty");
            }
        }
    }

    /**
     * Validate that a drive item is a file, not a folder
     *
     * @throws \InvalidArgumentException if item is a folder or not a file
     */
    private function validateItemIsFile(string $driveItemId): \Microsoft\Graph\Generated\Models\DriveItem
    {
        $driveItem = $this->graph->drives()
            ->byDriveId($this->driveId)
            ->items()
            ->byDriveItemId($driveItemId)
            ->get()
            ->wait();

        if ($driveItem->getFolder() !== null) {
            $this->logError('Attempted to create public permission for folder', [
                'drive_item_id' => $driveItemId,
                'drive_item_name' => $driveItem->getName(),
                'drive_item_path' => $driveItem->getWebUrl(),
                'folder_child_count' => $driveItem->getFolder()->getChildCount(),
            ]);

            throw new \InvalidArgumentException(
                "Cannot create public permission for folders. Item: {$driveItem->getName()} (folder with {$driveItem->getFolder()->getChildCount()} items)"
            );
        }

        // Additional validation: Check if drive item is actually a file
        if ($driveItem->getFile() === null) {
            $this->logError('Drive item is neither file nor folder', [
                'drive_item_id' => $driveItemId,
                'drive_item_name' => $driveItem->getName(),
            ]);

            throw new \InvalidArgumentException(
                "Cannot create public permission for non-file items. Item: {$driveItem->getName()}"
            );
        }

        return $driveItem;
    }
}
