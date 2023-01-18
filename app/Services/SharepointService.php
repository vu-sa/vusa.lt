<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\Children\ChildrenRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Drives\Item\Items\Item\Children\ChildrenRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Models\Drive;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\DriveItemCollectionResponse;
use Microsoft\Graph\Generated\Models\File;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Kiota\Authentication\PhpLeagueAuthenticationProvider;

/**
 * SharepointService
 * 
 * This class is used to interact with Sharepoint API
 * It has common methods for all Sharepoint API calls in a specific drive
 * 
 */
class SharepointService {
    
    protected $graphServiceClient;
    protected $siteId;
    
    public function __construct()
    {
        $tokenRequestContext = new ClientCredentialContext(
            config('filesystems.sharepoint.tenant_id'),
            config('filesystems.sharepoint.client_id'),
            config('filesystems.sharepoint.client_secret')
        );
        $scopes = ['https://graph.microsoft.com/.default'];
        $authProvider = new PhpLeagueAuthenticationProvider($tokenRequestContext, $scopes);
        $requestAdapter = new GraphRequestAdapter($authProvider);
        $graphServiceClient = new GraphServiceClient($requestAdapter);

        $this->graphServiceClient = $graphServiceClient;
        $this->siteId = config('filesystems.sharepoint.site_id');
    }

    private function getDrive($siteId = null) : Drive {
        $siteId = $siteId ?? $this->siteId;
        
        $response = $this->graphServiceClient->sitesById($siteId)->drive()->get();
        $drive = $response->wait();
        
        return $drive;
    }

    public function getDriveId($siteId = null): string {
        $siteId = $siteId ?? $this->siteId;
        
        return Cache::remember('ms_drive_id_for_site_' . $siteId, 3500, function () use ($siteId) {
            $drive = $this->getDrive($siteId);
            return $drive->getId();
        });
    }

    private function getDriveRootChildrens($driveId = null): DriveItemCollectionResponse {
        $driveId = $driveId ?? $this->getDriveId();

        $response = $this->graphServiceClient
            ->drivesById($driveId)
            ->root()->children()
            ->get();

        $driveItems = $response->wait();

        return $driveItems;
    }

    private function getDriveItem_General($driveId = null) {
        $driveItems = $this->getDriveRootChildrens();

        foreach ($driveItems->getValue() as $driveItem) {
            if ($driveItem->getName() === 'General') {
                return $driveItem;
            }
        }

        // return exception if no General folder found
        return(new Exception('General folder not found'));
    }

    public function getDriveItemId_General($driveId = null): string {
        $driveId = $driveId ?? $this->getDriveId();
        
        return Cache::remember('ms_drive_item_id_for_drive_' . $driveId . '_general_folder', 3500, function () use ($driveId) {
            $driveItem = $this->getDriveItem_General($driveId);
            return $driveItem->getId();
        });
    }

    private function getDriveItemChildren($driveId = null, $driveItemId = null): DriveItemCollectionResponse {
        $driveId = $driveId ?? $this->getDriveId();
        $driveItemId = $driveItemId ?? $this->getDriveItemId_General();

        $configuration = new ChildrenRequestBuilderGetRequestConfiguration();
        $queryParameters = new ChildrenRequestBuilderGetQueryParameters();

        $queryParameters->expand = ['listItem'];
        $configuration->queryParameters = $queryParameters;
        
        $response = $this->graphServiceClient
            ->drivesById($driveId)
            ->itemsById($driveItemId)
            ->children()
            ->get($configuration);

        $driveItems = $response->wait();

        return $driveItems;
    }
    
    /**
     * getDriveItemChildrenItems
     *
     * @param  mixed $driveId
     * @param  mixed $driveItemId
     * @return array<DriveItem>
     */
    public function getDriveItemChildrenItems($driveId = null, $driveItemId = null): array {
        $driveItems = $this->getDriveItemChildren($driveId, $driveItemId);

        return $driveItems->getValue();
    }
    
    /**
     * parseDriveItems
     *
     * @param  array<DriveItem> $driveItems
     * @return void
     */
    public function parseDriveItems(array $driveItems) {
        $parsedDriveItems = [];

        foreach ($driveItems as $driveItem) {
            $parsedDriveItems[] = [
                'id' => $driveItem->getId(),
                'name' => $driveItem->getName(),
                'file' => [
                    'mimeType' => $driveItem->getFile()->getMimeType()
                ],
                'size' => $driveItem->getSize(),
                'createdDateTime' => $driveItem->getCreatedDateTime(),
                'lastModifiedDateTime' => $driveItem->getLastModifiedDateTime(),
                'webUrl' => $driveItem->getWebUrl(),
                'listItem' => [
                    'fields' => [
                        'additionalData' => $driveItem->getListItem()->getFields()->getAdditionalData(),
                    ],
                ],
            ];
        }

        return $parsedDriveItems;
    }

    public function uploadFile($driveId = null, $driveItem = null, $file, $listItemData) {
        $driveId = $driveId ?? $this->getDriveId();
        $driveItemId = $driveItemId ?? $this->getDriveItemId_General();

        $driveItem = new DriveItem();
        $driveItem->setName($file->getClientOriginalName());
        $driveItem->setFile(new File());
        $driveItem->getFile()->setMimeType($file->getMimeType());
        $driveItem->setAdditionalData([
            'listItem' => [
                'fields' => $listItemData
            ]
        ]);

        $response = $this->graphServiceClient
            ->drivesById($driveId)
            ->itemsById($driveItemId)
            ->createUploadSession()->post($driveItem);
    }
}