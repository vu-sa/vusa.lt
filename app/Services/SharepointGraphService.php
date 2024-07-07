<?php

namespace App\Services;

use App\Models\SharepointFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchRequestItem;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\CreateLink\CreateLinkPostRequestBody;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\DriveItemItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Models\FieldValueSet;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Models\PermissionCollectionResponse;
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
    protected $graph;

    protected $graphApiBaseUrl;

    protected $siteId;

    protected $driveId;

    /**
     * __construct
     * Set for which sharepoint site and drive to interact with
     * If no siteId or driveId is provided, it will use the default values from config
     *
     * @param  mixed  $siteId
     * @param  mixed  $driveId
     * @return void
     */
    public function __construct(?string $siteId = null, ?string $driveId = null)
    {
        $tokenRequestContext = new ClientCredentialContext(
            config('filesystems.sharepoint.tenant_id'),
            config('filesystems.sharepoint.client_id'),
            config('filesystems.sharepoint.client_secret')
        );

        $this->graph = new GraphServiceClient($tokenRequestContext);
        $this->graphApiBaseUrl = 'https://graph.microsoft.com/v1.0/';

        $this->siteId = $siteId ?? config('filesystems.sharepoint.site_id');
        $this->driveId = $driveId ?? $this->getDrive()->getId();
    }

    private function getSite(): Models\Site
    {
        $site = $this->graph->sites()->bySiteId($this->siteId)->get()->wait();

        return $site;
    }

    private function getDrive(): Models\Drive
    {
        $drive = $this->graph->sites()->bySiteId($this->siteId)->drive()->get()->wait();

        return $drive;
    }

    /**
     * getDriveItemByPath
     *
     * Note: for some reason DriveItems are not returned
     *
     * @param  mixed  $path
     * @param  mixed  $siteId
     * @return array<Model\DriveItem>
     */
    public function getDriveItemByPath(string $path, $getChildren = false)
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

    /*public function getDriveItemByIdWithListItem(string $driveItemId): Models\DriveItem*/
    /*{*/
    /*    $requestConfiguration = new DriveItemItemRequestBuilderGetRequestConfiguration();*/
    /*    $queryParameters = DriveItemItemRequestBuilderGetRequestConfiguration::createQueryParameters();*/
    /**/
    /*    $queryParameters->expand = ['listItem'];*/
    /**/
    /*    $requestConfiguration->queryParameters = $queryParameters;*/
    /**/
    /*    $driveItem = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->get($requestConfiguration)->wait();*/
    /**/
    /*    return $driveItem;*/
    /*}*/

    public function updateDriveItemByPath(string $path, array $fields): Models\DriveItem
    {
        $path = rawurlencode($path);

        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}";

        $updatableDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root($path)->withUrl($sharepointPathFinal)->withUrl($sharepointPathFinal)->get()->wait();

        isset($fields['name']) ? $updatableDriveItem->setName($fields['name']) : null;

        $result = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($updatableDriveItem->getId())->patch($updatableDriveItem)->wait();

        return $result;
    }

    public function updateListItem(string $listId, $listItemId, array $fields): Models\FieldValueSet
    {
        /*dd($fields, $this->siteId, $listId, $listItemId);*/

        $requestConfiguration = new FieldsRequestBuilderPatchRequestConfiguration();
        $fieldValueSet = new FieldValueSet();

        $fieldValueSet->setAdditionalData($fields);

        $updatedListItem = $this->graph->sites()->bySiteId($this->siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->fields()->patch($fieldValueSet, $requestConfiguration)->wait();

        return $updatedListItem;
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

    public function getDriveItemPublicLink(string $driveItemId): ?string
    {
        $permissions = collect($this->getDriveItemPermissions($driveItemId)->getValue());

        $permission = $permissions->filter(function (Models\Permission $permission) {
            return $permission->getLink() && $permission->getLink()->getScope() == 'anonymous';
        })->first();

        return $permission ? $permission->getLink()->getWebUrl() : null;
    }

    public function createPublicPermission(string $driveItemId): string
    {
        $requestBody = new CreateLinkPostRequestBody();

        $requestBody->setType('view');
        $requestBody->setScope('anonymous');
        $requestBody->setExpirationDateTime(new \DateTime('2025-01-01T00:00:00Z'));

        $permission = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($driveItemId)->createLink()->post($requestBody)->wait();

        return $permission->getLink()->getWebUrl();
    }

    public function uploadDriveItem(string $filePath, UploadedFile $file): Models\DriveItem
    {
        $factory = new Psr17Factory();

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
     * parseDriveItems
     *
     * @param  Models\Drive  $driveItems
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
}
