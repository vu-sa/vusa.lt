<?php

namespace App\Services;

use App\Enums\SharepointConfigEnum;
use App\Enums\SharepointPermissionTypeEnum;
use App\Enums\SharepointScopeEnum;
use App\Exceptions\SharepointDeltaExpiredException;
use App\Exceptions\SharepointThrottledException;
use App\Support\StagingProtection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchRequestItem;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\CreateLink\CreateLinkPostRequestBody;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\Delta\DeltaRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\DriveItemItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\ListItem\ListItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\FieldValueSet;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Models\PermissionCollectionResponse;
use Microsoft\Graph\Generated\Sites\Item\Lists\Item\Items\Item\DriveItem\DriveItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Sites\Item\Lists\Item\Items\Item\Fields\FieldsRequestBuilderPatchRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
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
    private const int DEFAULT_PERMISSION_EXPIRY_DAYS = 365;

    /** A longer Retry-After ends the caller's run instead of holding a worker asleep. */
    public const int MAX_THROTTLE_WAIT_SECONDS = 120;

    /**
     * SharePoint Graph API Service
     *
     * This service uses technical constants from SharepointConfigEnum for API URLs,
     * retry logic, timeouts, and other static configuration values.
     *
     * @see SharepointConfigEnum For static technical configuration
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
            $this->graphApiBaseUrl = SharepointConfigEnum::API_BASE_URL->label();

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
     * Get a DriveItem object by path (not parsed into collection).
     * Returns the actual Microsoft Graph DriveItem model.
     */
    public function getDriveItemObjectByPath(string $path): ?DriveItem
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
        } catch (ODataError|\Exception $e) {
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
    public function getDriveItemById(string $driveItemId): ?DriveItem
    {
        try {
            $requestConfiguration = new DriveItemItemRequestBuilderGetRequestConfiguration;
            $queryParameters = DriveItemItemRequestBuilderGetRequestConfiguration::createQueryParameters();

            $queryParameters->expand = ['listItem'];

            $requestConfiguration->queryParameters = $queryParameters;

            $driveItem = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->get($requestConfiguration)->wait();

            return $driveItem;
        } catch (\Exception $e) {
            Log::warning('Failed to get drive item by ID', [
                'driveItemId' => $driveItemId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download the raw content of a drive item.
     *
     * Intended for tiny files (e.g. `.url` shortcuts); reads at most
     * $maxBytes so a mis-typed call can never pull a large document into
     * memory.
     *
     * @return string|null Null when the item has no content or the request failed.
     */
    public function getDriveItemContent(string $driveItemId, int $maxBytes = 65536): ?string
    {
        try {
            $stream = $this->graph->drives()
                ->byDriveId($this->driveId)
                ->items()
                ->byDriveItemId($driveItemId)
                ->content()
                ->get()
                ->wait();

            if ($stream === null) {
                return null;
            }

            $content = '';

            while (! $stream->eof() && strlen($content) < $maxBytes) {
                $chunk = $stream->read($maxBytes - strlen($content));

                if ($chunk === '') {
                    break;
                }

                $content .= $chunk;
            }

            $stream->close();

            return $content === '' ? null : $content;
        } catch (\Exception $e) {
            Log::warning('Failed to download drive item content', [
                'driveItemId' => $driveItemId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getDriveItemByListItem(string $siteId, string $listId, string $listItemId): DriveItem
    {
        $requestConfiguration = new DriveItemRequestBuilderGetRequestConfiguration;
        $queryParameters = DriveItemRequestBuilderGetRequestConfiguration::createQueryParameters();

        $queryParameters->expand = ['thumbnails'];

        $requestConfiguration->queryParameters = $queryParameters;

        $driveItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->driveItem()->get($requestConfiguration)->wait();

        return $driveItem;
    }

    public function updateDriveItemByPath(string $path, array $fields): ?DriveItem
    {
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

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

    public function getListItem(string $siteId, string $listId, string $listItemId): FieldValueSet
    {
        try {
            $listItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->fields()->get()->wait();

            return $listItem;
        } catch (ODataError $e) {
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

    public function updateListItem(string $listId, string $listItemId, array $fields): FieldValueSet
    {
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

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

    protected function getDriveItemPermissions(string $driveItemId): PermissionCollectionResponse
    {
        $permissions = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->permissions()->get()->wait();

        return $permissions;
    }

    public function getDriveItemPublicLink(string $driveItemId): ?Models\Permission
    {
        $permissions = collect($this->getDriveItemPermissions($driveItemId)->getValue());

        $permission = $permissions->filter(function (Models\Permission $permission) use ($driveItemId) {
            if (! $permission->getLink()
                || $permission->getLink()->getScope() !== 'anonymous'
                || $permission->getExpirationDateTime() !== null
                || $permission->getInheritedFrom() !== null) {
                return false;
            }

            $url = $permission->getLink()->getWebUrl();

            // Treat links without webUrl as invalid to avoid overwriting a working anonymous_url.
            if ($url === null) {
                $this->logWarning('Rejecting anonymous permission without a webUrl', [
                    'drive_item_id' => $driveItemId,
                    'permission_id' => $permission->getId(),
                ]);

                return false;
            }

            // Check URL type - must be file (:b: or :w:), not folder (:f:)
            if ($this->isFolderUrl($url)) {
                $this->logWarning('Rejecting folder URL permission on file', [
                    'drive_item_id' => $driveItemId,
                    'permission_id' => $permission->getId(),
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
        StagingProtection::ensureSharepointIsWritable($siteId ?? $this->siteId, $this->driveId);
        $this->validateNotEmpty(['driveItemId' => $driveItemId]);

        // Validate item is a file, not folder
        $driveItem = $this->validateItemIsFile($driveItemId);

        return $this->executeWithRetry(function () use ($siteId, $driveItemId, $datetime, $driveItem) {
            $siteId ??= $this->siteId;
            $datetime ??= Carbon::now()->addDays(self::DEFAULT_PERMISSION_EXPIRY_DAYS);

            $requestBody = new CreateLinkPostRequestBody;
            $requestBody->setType(SharepointPermissionTypeEnum::VIEW->label());
            $requestBody->setScope(SharepointScopeEnum::ANONYMOUS->label());

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
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

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

    public function uploadDriveItem(string $filePath, UploadedFile $file): DriveItem
    {
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

        $factory = new Psr17Factory;

        $stream = $factory->createStreamFromFile($file->getPathname(), 'r');

        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$filePath}:/content?\$expand=listItem&@microsoft.graph.conflictBehavior=rename";

        $uploadedDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->content()->put($stream)->wait();

        return $uploadedDriveItem;
    }

    public function deleteDriveItem(string $driveItemId): void
    {
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

        $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->delete()->wait();
    }

    /**
     * Upload a .url shortcut file to SharePoint.
     * Creates necessary parent folders if they don't exist.
     *
     * @param  string  $filePath  The full path including filename (e.g., "Folder/Subfolder/shortcut.url")
     * @param  string  $content  The .url file content
     */
    public function uploadUrlShortcut(string $filePath, string $content): DriveItem
    {
        StagingProtection::ensureSharepointIsWritable($this->siteId, $this->driveId);

        $factory = new Psr17Factory;

        $stream = $factory->createStream($content);

        // Use fail conflict behavior since we don't want to overwrite existing shortcuts
        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$filePath}:/content?\$expand=listItem&@microsoft.graph.conflictBehavior=fail";

        $uploadedDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root()->withUrl($sharepointPathFinal)->content()->put($stream)->wait();

        return $uploadedDriveItem;
    }

    /**
     * One page of the drive's change feed. `null` starts a full listing; pass the previous
     * page's nextLink, or a stored deltaLink, to continue.
     *
     * @return array{items: list<DriveItem>, nextLink: ?string, deltaLink: ?string}
     *
     * @throws SharepointDeltaExpiredException when Graph no longer accepts the deltaLink
     */
    public function getDriveDeltaPage(?string $link = null): array
    {
        $builder = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId('root')->delta();

        $configuration = new DeltaRequestBuilderGetRequestConfiguration;
        $configuration->queryParameters = DeltaRequestBuilderGetRequestConfiguration::createQueryParameters();
        $configuration->queryParameters->select = [
            'id', 'name', 'file', 'folder', 'root', 'deleted', 'eTag', 'lastModifiedDateTime',
            'parentReference', 'sharepointIds', 'webUrl',
        ];

        try {
            $response = $link === null
                ? $builder->get($configuration)->wait()
                : $builder->withUrl($link)->get()->wait();
        } catch (ApiException $e) {
            if ($e->getResponseStatusCode() === 410) {
                throw new SharepointDeltaExpiredException('SharePoint delta link expired', previous: $e);
            }

            throw $e;
        }

        return [
            'items' => array_values($response?->getValue() ?? []),
            'nextLink' => $response?->getOdataNextLink(),
            'deltaLink' => $response?->getOdataDeltaLink(),
        ];
    }

    /**
     * List item fields of many drive items, batched 20 per Graph request. Throttled items are
     * retried after SharePoint's Retry-After. Items that fail for a reason that may pass (throttling,
     * a server error, a refused token) are listed as `transient`; any other missing item is a
     * permanent failure of that file's data.
     *
     * @param  list<string>  $driveItemIds
     * @return array{items: array<string, array{eTag: ?string, fields: array<string, mixed>}>, transient: list<string>}
     *
     * @throws SharepointThrottledException when SharePoint asks for a longer pause than is worth sleeping through
     */
    public function getListItemsForDriveItems(array $driveItemIds, int $maxThrottleRetries = 6): array
    {
        $result = [];
        $transient = [];
        $pending = $driveItemIds;

        for ($attempt = 0; $pending !== [] && $attempt <= $maxThrottleRetries; $attempt++) {
            $throttled = [];
            $waitSeconds = 0;

            foreach (array_chunk($pending, 20) as $chunk) {
                // Once SharePoint throttles, the rest of this pass would only be throttled too.
                if ($throttled !== []) {
                    array_push($throttled, ...$chunk);

                    continue;
                }

                $response = $this->postListItemBatch($chunk);

                foreach ($chunk as $driveItemId) {
                    $item = $response->getResponse($driveItemId);
                    $status = $item->getStatusCode();

                    if ($status === 429 || $status === 503) {
                        $throttled[] = $driveItemId;
                        $waitSeconds = max($waitSeconds, $this->retryAfterSeconds($item->getHeaders()));

                        continue;
                    }

                    if ($status === null || $status >= 500 || $status === 401 || $status === 403) {
                        $this->logWarning('List item lookup failed in batch, will retry', ['drive_item_id' => $driveItemId, 'status' => $status]);
                        $transient[] = $driveItemId;

                        continue;
                    }

                    $listItem = $this->parseListItem($response, $driveItemId, $status);

                    if ($listItem !== null) {
                        $result[$driveItemId] = $listItem;
                    }
                }
            }

            $pending = $throttled;

            if ($pending !== [] && $attempt < $maxThrottleRetries) {
                if ($waitSeconds > self::MAX_THROTTLE_WAIT_SECONDS) {
                    throw new SharepointThrottledException($waitSeconds);
                }

                $this->logInfo('SharePoint throttled list item lookups, waiting', ['items' => count($pending), 'seconds' => $waitSeconds]);
                Sleep::for($waitSeconds)->seconds();
            }
        }

        if ($pending !== []) {
            $this->logWarning('List item lookups still throttled after retries', ['items' => count($pending)]);
            array_push($transient, ...$pending);
        }

        return ['items' => $result, 'transient' => $transient];
    }

    /**
     * @param  list<string>  $driveItemIds
     */
    private function postListItemBatch(array $driveItemIds): BatchResponseContent
    {
        $batch = new BatchRequestContent(array_map(function (string $driveItemId): BatchRequestItem {
            $configuration = new ListItemRequestBuilderGetRequestConfiguration;
            $configuration->queryParameters = ListItemRequestBuilderGetRequestConfiguration::createQueryParameters();
            $configuration->queryParameters->expand = ['fields'];

            $request = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)
                ->listItem()->toGetRequestInformation($configuration);

            return new BatchRequestItem($request, $driveItemId);
        }, $driveItemIds));

        return $this->executeWithRetry(
            fn () => (new BatchRequestBuilder($this->graph->getRequestAdapter()))->postAsync($batch)->wait(),
            'getListItemsForDriveItems',
        );
    }

    /**
     * @return array{eTag: ?string, fields: array<string, mixed>}|null
     */
    private function parseListItem(BatchResponseContent $response, string $driveItemId, ?int $status): ?array
    {
        if ($status === null || $status >= 300) {
            $this->logWarning('List item lookup failed in batch', ['drive_item_id' => $driveItemId, 'status' => $status]);

            return null;
        }

        try {
            $listItem = $response->getResponseBody($driveItemId, Models\ListItem::class);
        } catch (\Throwable $e) {
            $this->logWarning('List item lookup failed in batch', ['drive_item_id' => $driveItemId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $listItem instanceof Models\ListItem) {
            return null;
        }

        $fields = $listItem->getFields()?->getAdditionalData() ?? [];

        return ['eTag' => $fields['@odata.etag'] ?? $listItem->getETag(), 'fields' => $fields];
    }

    /**
     * Retry-After is either seconds or an HTTP date; Graph's guidance is to wait the whole of it.
     *
     * @param  array<string, string|list<string>>|null  $headers
     */
    private function retryAfterSeconds(?array $headers): int
    {
        foreach ($headers ?? [] as $name => $value) {
            if (strtolower((string) $name) !== 'retry-after') {
                continue;
            }

            $value = trim((string) (is_array($value) ? ($value[0] ?? '') : $value));

            if (is_numeric($value)) {
                return max(1, (int) $value);
            }

            $until = strtotime($value);

            return $until === false ? 10 : max(1, $until - time());
        }

        return 10;
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
    private function maskUrl(?string $url): string
    {
        if ($url === null) {
            return 'none';
        }

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
        $maxRetries ??= (int) SharepointConfigEnum::MAX_RETRIES->label();
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
                if ($e instanceof ODataError) {
                    $errorDetails['odata_error'] = $e->getError()?->getMessage() ?? 'No OData error message';
                    $errorDetails['odata_code'] = $e->getError()?->getCode() ?? 'No code';
                }

                // Log the exception class for debugging
                $errorDetails['exception_class'] = $e::class;

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
                $delay = (int) SharepointConfigEnum::RETRY_DELAY_MS->label() * 2 ** ($attempt - 1);
                Sleep::for($delay)->milliseconds();

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
    private function validateItemIsFile(string $driveItemId): DriveItem
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

    /**
     * Check if a SharePoint URL points to a folder rather than a file.
     *
     * SharePoint URLs use :f: for folders, :b: for binary files, :w: for Word docs, etc.
     */
    private function isFolderUrl(?string $url): bool
    {
        return $url !== null && str_contains($url, ':f:');
    }
}
