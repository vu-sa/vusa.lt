<?php

namespace App\Listeners;

use App\Events\FileableNameUpdated;
use App\Services\ResourceServices\SharepointFileService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSharepointFolder
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\FileableNameUpdated  $event
     * @return void
     */
    public function handle(FileableNameUpdated $event)
    {
        $fileable = $event->fileable;

        // TODO: not always name
        $fileable->name = $fileable->getOriginal()['name'];

        $sharepointService = new SharepointFileService();
        $path = $sharepointService->pathForFileableDriveItem($fileable);

        $sharepointGraph = new \App\Services\SharepointGraphService();
        
        try {
            $driveItem = $sharepointGraph->updateDriveItemByPath($path, [
                'name' => $fileable->getChanges()['name'],
            ]);
        } catch (ClientException $e) {
        }
    }
}