<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Beta\Microsoft\Graph\Model as BetaModel;
use Laravel\Socialite\Facades\Socialite;

class SharepointAppGraph {
    
    public function __construct()
    {
        if (Cache::has('ms_application_token')) {
            $token = Crypt::decryptString(Cache::get('ms_application_token'));
        } else {
            $url = 'https://login.microsoftonline.com/' . config('filesystems.sharepoint.tenant_id') . '/oauth2/v2.0/token';

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
    }

    public function getSiteById(string $site_id) : Model\Site
    {
        if (Cache::has('ms_site_' . $site_id)) {
            return Cache::get('ms_site_' . $site_id);
        }
        
        $site = $this->graph->createRequest("GET", "/sites/{$site_id}")
            ->setReturnType(Model\Site::class)
            ->execute();

        // put cache for just under an hour
        Cache::put('ms_site_' . $site_id, $site, 3500);

        return $site;
    }

    public function getDriveBySite(string $siteId) : Model\Drive
    {
        if (Cache::has('ms_drive_' . $siteId)) {
            return Cache::get('ms_drive_' . $siteId);
        }

        $drive = $this->graph->createRequest("GET", "/sites/{$siteId}/drive")
            ->setReturnType(Model\Drive::class)
            ->execute();

        Cache::put('ms_drive_' . $siteId, $drive, 3600);

        return $drive;
    }

    public function getDriveChildren(string $driveId) : array
    {
        if (Cache::has('ms_drive_children_' . $driveId)) {
            return Cache::get('ms_drive_children_' . $driveId);
        }
        
        $driveItems = $this->graph->createRequest("GET", "/drives/{$driveId}/root/children")
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        
        Cache::put('ms_drive_children_' . $driveId, $driveItems, 3600);

        return $driveItems;
    }

    public function getDriveItemByIdWithListItem(string $driveId, string $driveItemId) : Model\DriveItem 
    {
        $driveItem = $this->graph->createRequest("GET", "/drives/{$driveId}/items/{$driveItemId}?\$expand=listItem")
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        return $driveItem;
    }

    public function updateListItem(string $siteId, string $listId, $listItemId, array $fields): Model\ListItem 
    {
        $updatedMetadata = 
        $this->graph->createRequest("PATCH", "/sites/{$siteId}/lists/{$listId}/items/{$listItemId}/fields")
            ->attachBody(
                $fields
            )
            ->setReturnType(Model\ListItem::class)
            ->execute();

        return $updatedMetadata;
    }

    public function getDriveItemChildren($driveId, $driveItemId) : array
    {
        if (Cache::has('ms_drive_item_children_' . $driveItemId)) {
            return Cache::get('ms_drive_item_children_' . $driveItemId);
        }
        
        $driveItems = $this->graph->createRequest("GET", "/drives/{$driveId}/items/{$driveItemId}/children?\$expand=listItem")
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        Cache::put('ms_drive_item_children_' . $driveItemId, $driveItems, 30);

        return $driveItems;
    }

    public function getGlobalTermStore() 
    {
        
        if (Cache::has('ms_global_term_store')) {
            return Cache::get('ms_global_term_store');
        }

        // this needs delegated permissions
        if (is_null($token = auth()->user()->microsoft_token)) {
            return abort(500, 'No token');
        }

        $this->graph->setAccessToken($token);
        
        try {
            $termStore = $this->graph->setApiVersion("beta")
                ->createRequest("GET", "/termStore/sets/" . config('filesystems.sharepoint.main_term_set_id') .  "?\$expand=children")
                ->setReturnType(BetaModel\Entity::class)
                ->execute();
        } catch (\Exception $e) {
           return redirect()->route('microsoft.redirect');
        }

        // somehow fix
        Cache::put('ms_global_term_store', $termStore, 3600);

        return $termStore;
    }

    public function uploadDriveItem(string $driveId, string $driveItemId, string $fileName, $content) : Model\DriveItem 
    {
        $uploadedDriveItem = $this->graph->createRequest("PUT", "/drives/{$driveId}/items/{$driveItemId}:/{$fileName}:/content?\$expand=listItem")
            ->addHeaders(['Content-Type' => 'text/plain'])
            ->attachBody($content)
            ->setReturnType(Model\DriveItem::class)
            ->execute();

        return $uploadedDriveItem;
    }
}