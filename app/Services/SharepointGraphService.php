<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Institution;
use App\Models\SharepointFile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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
    protected $graph;

    protected $graphApiBaseUrl;

    protected $siteId;

    protected $driveId;

    protected $listId;

    /**
     * __construct
     * Set for which sharepoint site and drive to interact with
     * If no siteId or driveId is provided, it will use the default values from config
     *
     * @param  mixed  $siteId
     * @param  mixed  $driveId
     * @return void
     */
    public function __construct(?string $siteId = null, ?string $driveId = null, ?string $listId = null)
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
        $this->listId = $listId;
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

    public function getDriveItemByListItem(string $siteId, string $listId, string $listItemId): Models\DriveItem
    {
        $requestConfiguration = new DriveItemRequestBuilderGetRequestConfiguration;
        $queryParameters = DriveItemRequestBuilderGetRequestConfiguration::createQueryParameters();

        $queryParameters->expand = ['thumbnails'];

        $requestConfiguration->queryParameters = $queryParameters;

        $driveItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->driveItem()->get($requestConfiguration)->wait();

        return $driveItem;
    }

    public function updateDriveItemByPath(string $path, array $fields): Models\DriveItem
    {
        $path = rawurlencode($path);

        $sharepointPathFinal = $this->graphApiBaseUrl.'drives/'.$this->driveId.'/root:'."/{$path}";

        $updatableDriveItem = $this->graph->drives()->byDriveId($this->driveId)->root($path)->withUrl($sharepointPathFinal)->withUrl($sharepointPathFinal)->get()->wait();

        isset($fields['name']) ? $updatableDriveItem->setName($fields['name']) : null;

        $result = $this->graph->drives()->byDriveId($this->driveId)->items()->byDriveItemId($updatableDriveItem->getId())->patch($updatableDriveItem)->wait();

        return $result;
    }

    public function getListItem(string $siteId, string $listId, $listItemId): Models\FieldValueSet
    {
        $listItem = $this->graph->sites()->bySiteId($siteId)->lists()->byListId($listId)->items()->byListItemId($listItemId)->fields()->get()->wait();

        return $listItem;
    }

    public function updateListItem(string $listId, $listItemId, array $fields): Models\FieldValueSet
    {
        /*dd($fields, $this->siteId, $listId, $listItemId);*/

        $requestConfiguration = new FieldsRequestBuilderPatchRequestConfiguration;
        $fieldValueSet = new FieldValueSet;

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

    public function getDriveItemPublicLink(string $driveItemId): ?Models\Permission
    {
        $permissions = collect($this->getDriveItemPermissions($driveItemId)->getValue());

        $permission = $permissions->filter(function (Models\Permission $permission) {
            return $permission->getLink() && $permission->getLink()->getScope() == 'anonymous' && $permission->getExpirationDateTime() === null;
        })->first();

        return $permission ?? null;
    }

    public function createPublicPermission(?string $siteId, string $driveItemId, Carbon|false|null $datetime = null): Models\Permission
    {
        $siteId = $siteId ?? $this->siteId;
        $datetime = $datetime ?? Carbon::now()->addYear(1)->subUTCMonth();

        $requestBody = new CreateLinkPostRequestBody;

        $requestBody->setType('view');
        $requestBody->setScope('anonymous');

        if ($datetime !== false) {
            $requestBody->setExpirationDateTime($datetime);
        }

        $sharepointPathFinal = "{$this->graphApiBaseUrl}sites/{$siteId}/drive/items/{$driveItemId}/createLink";

        // This is the wrong chain, but it should work
        $permission = $this->graph->drives()->byDriveId($driveItemId)->items()->byDriveItemId($driveItemId)->createLink()->withUrl($sharepointPathFinal)->post($requestBody)->wait();

        return $permission;
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

    /**
     * [TODO:description]
     *
     * @param Collection<Document> documentColection
     */
    public function batchProcessDocuments(EloquentCollection $documentColection)
    {

        // First, get the drive item and associated data
        $batch = new BatchRequestContent(
            $documentColection->map(function (Document $document) {

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

        // Get permissions without anonymous url
        $driveItemsWithoutAnonymousUrl = $driveItemCollections->filter(function ($driveItem) {
            return collect($driveItem['permissions'])->contains(function ($permission) {
                $hasAnonymous = isset($permission['link']['scope']) ? $permission['link']['scope'] === 'anonymous' : false;

                $hasPassword = isset($permission['hasPassword']) ? $permission['hasPassword'] : false;

                return ! $hasAnonymous || $hasPassword;
            });
        });

        // Add anonymous url to drive items without it
        if ($driveItemsWithoutAnonymousUrl->isNotEmpty()) {
            $batch = new BatchRequestContent(
                $driveItemsWithoutAnonymousUrl->map(function (array $driveItem) {

                    $requestBody = new CreateLinkPostRequestBody;

                    $requestBody->setType('view');
                    $requestBody->setScope('anonymous');

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
        $documentColection->each(function (Document $document) use ($driveItemCollections) {
            // TODO: handle batch update (need to set to current model)
            $driveItem = $driveItemCollections->get($document->sharepoint_id);

            $document->name = $driveItem['name'];
            $document->title = isset($driveItem['listItem']['fields']['Title']) ? $driveItem['listItem']['fields']['Title'] : $driveItem['name'];
            $document->eTag = $driveItem['listItem']['eTag'];
            $document->document_date = isset($driveItem['listItem']['fields']['Date']) ? Carbon::parseFromLocale(time: $driveItem['listItem']['fields']['Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius') : null;

            $document->language = isset($driveItem['listItem']['fields']['Language']) ? $driveItem['listItem']['fields']['Language'] : null;
            $document->content_type = isset($driveItem['listItem']['fields']['Turinys']['Label']) ? $driveItem['listItem']['fields']['Turinys']['Label'] : null;

            $document->summary = $driveItem['listItem']['fields']['Summary'] ?? null;
            /*$document->thumbnail_url = $driveItem['thumbnails'][0]['large']['url'];*/

            $document->anonymous_url = collect($driveItem['permissions'])->filter(function ($permission) {

                $isAnonymous = isset($permission['link']['scope']) ? $permission['link']['scope'] === 'anonymous' : false;
                $hasPassword = isset($permission['hasPassword']) ? $permission['hasPassword'] : false;

                return $isAnonymous && ! $hasPassword;
            })->first()['link']['webUrl'];

            $document->checked_at = Carbon::now();

            $institutionField = 'Padalinys'; // Entity

            if (isset($driveItem['listItem']['fields'][$institutionField]['Label'])) {
                $document->institution()->associate(Institution::query()->where('name->lt', $driveItem['listItem']['fields'][$institutionField]['Label'])->orWhere('short_name->lt', $driveItem['listItem']['fields'][$institutionField]['Label'])->first());
            }

            $document->save();
        });

        return $documentColection;

    }
}
