<?php

namespace App\Services;

use App\Models\SharepointFile;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

/**
 * SharepointGraphService
 *
 * This class is used to interact with Sharepoint API
 * It has common methods for all Sharepoint API calls in a specific drive
 */
class SharepointGraphService
{
    protected $graph;

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
    public function __construct(string $siteId = null, string $driveId = null)
    {
        if (Cache::has('ms_application_token')) {
            $token = Crypt::decryptString(Cache::get('ms_application_token'));
        } else {
            $url = 'https://login.microsoftonline.com/'.config('filesystems.sharepoint.tenant_id').'/oauth2/v2.0/token';

            $token = json_decode(Http::asForm()->post($url, [
                'client_id' => config('filesystems.sharepoint.client_id'),
                'client_secret' => config('filesystems.sharepoint.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])->getBody()->getContents());
            $token = $token->access_token;

            // put cache for just under an hour
            Cache::put('ms_application_token', Crypt::encryptString($token), 3500);
        }

        $this->graph = new Graph();
        $this->graph->setAccessToken($token);
        $this->siteId = $siteId ?? config('filesystems.sharepoint.site_id');
        $this->driveId = $driveId ?? $this->getDrive()->getId();
    }

    private function getSite(): Model\Site
    {
        return Cache::remember('ms_site_'.$this->siteId, 3600, function () {
            $site = $this->graph->createRequest('GET', "/sites/{$this->siteId}")
                ->setReturnType(Model\Site::class)
                ->execute();

            return $site;
        });
    }

    private function getDrive(): Model\Drive
    {
        return Cache::remember('ms_drive_'.$this->siteId, 3600, function () {
            $drive = $this->graph->createRequest('GET', "/sites/{$this->siteId}/drive")
                ->setReturnType(Model\Drive::class)
                ->execute();

            return $drive;
        });
    }

    /**
     * getDriveItemByPath
     *
     * @param  mixed  $path
     * @param  mixed  $siteId
     * @return array<Model\DriveItem>
     */
    public function getDriveItemByPath(string $path, $getChildren = false): Collection
    {
        // encode path
        $path = rawurlencode($path);
        $childrenPath = $getChildren ? ':/children' : '';

        try {
            $driveItems = $this->graph->createRequest('GET', "/drives/{$this->driveId}/root:/{$path}{$childrenPath}?\$expand=listItem,thumbnails")
                ->setReturnType(Model\DriveItem::class)
                ->execute();
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                return collect([]);
            }

            throw $e;
        }

        // wrap in array if not array
        if (! is_array($driveItems)) {
            $driveItems = [$driveItems];
        }

        return $this->parseDriveItems($driveItems);
    }

    public function getDriveItemByIdWithListItem(string $driveItemId): Model\DriveItem
    {
        $driveItem = $this->graph->createRequest('GET', "/drives/{$this->driveId}/items/{$driveItemId}?\$expand=listItem")
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        return $driveItem;
    }

    public function updateDriveItemByPath(string $path, array $fields): Model\DriveItem
    {
        $path = rawurlencode($path);

        $updatedDriveItem = $this->graph->createRequest('PATCH', "/drives/{$this->driveId}/root:/{$path}")
            ->attachBody(
                $fields
            )
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        return $updatedDriveItem;
    }

    public function updateListItem(string $listId, $listItemId, array $fields): Model\ListItem
    {
        $updatedListItem =
        $this->graph->createRequest('PATCH', "/sites/{$this->siteId}/lists/{$listId}/items/{$listItemId}/fields")
            ->attachBody(
                $fields
            )
            ->setReturnType(Model\ListItem::class)
            ->execute();

        return $updatedListItem;
    }

    public function getDriveItemsChildrenByPaths(array $paths)
    {
        $pathCollection = collect($paths);
        $id = 0;

        $batch_request_body = ['requests' => $pathCollection->map(function ($path) use (&$id) {
            $id++;

            $path = rawurlencode($path);

            return [
                'id' => $id,
                'method' => 'GET',
                'url' => "/drives/{$this->driveId}/root:/{$path}:/children?\$expand=listItem,thumbnails",
            ];
        })->values()->toArray(),
        ];

        $batch_response = $this->graph->createRequest('POST', '/$batch')
            ->attachBody($batch_request_body)
            ->execute()->getBody();

        $driveItems = [];

        foreach ($batch_response['responses'] as $response) {
            if ($response['status'] === 404) {
                continue;
            }

            // create DriveItem for each response
            // $response = new HttpResponse($response['body'], $response['status'], $response['headers']);
            collect($response['body']['value'])->each(function ($driveItem) use (&$driveItems) {
                $driveItems[] = new Model\DriveItem($driveItem);
            });
        }

        return $this->parseDriveItems($driveItems);
    }

    protected function getDriveItemPermissions(string $driveItemId): array
    {
        $permissions = $this->graph->createRequest('GET', "/drives/{$this->driveId}/items/{$driveItemId}/permissions")
            ->setReturnType(Model\Permission::class)
            ->execute();

        return $permissions;
    }

    /**
     * parsePermissionsForPublicLink
     *
     * @param  array<Model\Permission>  $permissions
     * @return ?Model\Permission
     */
    protected function parsePermissionsForPublicLink(array $permissions): ?Model\Permission
    {
        $permissions = collect($permissions);

        // filter collection for public link. public permissions have a link property which has scope to anonymous
        $publicLinkPermission = $permissions->filter(function ($permission) {
            return $permission->getLink() && $permission->getLink()->getScope() == 'anonymous';
        })->first();

        return $publicLinkPermission;
    }

    public function getDriveItemPublicLink(string $driveItemId): ?Model\Permission
    {
        $permissions = $this->getDriveItemPermissions($driveItemId);

        return $this->parsePermissionsForPublicLink($permissions);
    }

    public function createPublicPermission(string $driveItemId): ?Model\Permission
    {
        $permission = $this->graph->createRequest('POST', "/drives/{$this->driveId}/items/{$driveItemId}/createLink")
            ->attachBody([
                'type' => 'view',
                'scope' => 'anonymous',
            ])
            ->setReturnType(Model\Permission::class)
            ->execute();

        return $permission;
    }

    public function uploadDriveItem(string $filePath, $content): Model\DriveItem
    {
        // if file is more than 100 MB, unauthorized error is thrown
        abort_if(strlen($content) > 64000000, 403, 'Kolkas neleidžiama įkelti didesnių nei 64MB failų');

        // check if file is more than 4MB
        if (strlen($content) > 4000000) {
            $uploadSession = $this->graph->createRequest('POST', "/drives/{$this->driveId}/root:/{$filePath}:/createUploadSession")
                ->attachBody([
                    'item' => [
                        '@microsoft.graph.conflictBehavior' => 'rename',
                    ],
                ])
                ->setReturnType(Model\UploadSession::class)
                ->execute();

            $uploadUrl = $uploadSession->getUploadUrl($content);

            $uploadedDriveItem = $this->graph->createRequest('PUT', $uploadUrl)
                ->addHeaders(
                    ['Content-Length' => strlen($content),
                        'Content-Range' => 'bytes 0-'.(strlen($content) - 1).'/'.strlen($content),
                    ])
                ->attachBody($content)
                ->setReturnType(Model\DriveItem::class)
                ->execute();

            $uploadedDriveItem = $this->getDriveItemByIdWithListItem($uploadedDriveItem->getId());
        } else {
            $uploadedDriveItem = $this->graph->createRequest('PUT', "/drives/{$this->driveId}/root:/{$filePath}:/content?expand=listItem")
                ->attachBody($content)
                ->setReturnType(Model\DriveItem::class)
                ->execute();
        }

        return $uploadedDriveItem;
    }

    public function deleteDriveItem(string $driveItemId): void
    {
        $this->graph->createRequest('DELETE', "/drives/{$this->driveId}/items/{$driveItemId}")
            ->execute();
    }

    /**
     * parseDriveItems
     *
     * @param  array<Model\DriveItem>  $driveItems
     * @return Collection
     */
    private function parseDriveItems(array|Model\DriveItem $driveItems)
    {
        $driveItems = collect($driveItems);

        // get all driveitem ids
        $driveItemIds = $driveItems->map(function ($driveItem) {
            return $driveItem->getId();
        })->toArray();

        // load all sharepointFile models wherein sharepointfile.sharepoint_id
        // is in $driveItemIds
        $sharepointFiles = SharepointFile::whereIn('sharepoint_id', $driveItemIds)->with('fileables.fileable', 'comments')->get();

        $parsedDriveItems = $driveItems->map(function ($driveItem) use ($sharepointFiles) {
            return [
                'id' => $driveItem->getId(),
                'sharepointFile' => $sharepointFiles->filter(function ($sharepointFile) use ($driveItem) {
                    return $sharepointFile->sharepoint_id == $driveItem->getId();
                })->first(),
                'name' => $driveItem->getName(),
                'file' => $driveItem->getFile()?->getProperties(),
                // if driveitem is a file, get content
                'folder' => $driveItem->getFolder()?->getProperties(),
                'size' => $driveItem->getSize(),
                'createdDateTime' => $driveItem->getCreatedDateTime(),
                'lastModifiedDateTime' => $driveItem->getLastModifiedDateTime(),
                'webUrl' => $driveItem->getWebUrl(),
                'listItem' => [
                    'fields' => [
                        'properties' => $driveItem->getListItem()?->getFields()->getProperties(),
                    ],
                ],
                'permissions' => $driveItem->getPermissions(),
                'thumbnails' => collect($driveItem->getThumbnails())->map(function ($thumbnail) {
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
