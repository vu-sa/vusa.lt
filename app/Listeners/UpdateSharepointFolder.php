<?php

namespace App\Listeners;

use App\Events\FileableNameUpdated;
use App\Services\ResourceServices\SharepointFileService;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSharepointFolder implements ShouldQueue
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
     * @return void
     */
    public function handle(FileableNameUpdated $event)
    {
        $fileable = $event->fileable;

        // Check if the "name" or "title" property exists in the $fileable object
        if (! is_null($fileable->start_time)) {
            $fileable->start_time = $fileable->getOriginal()['start_time'];
            $newName = Carbon::parse($fileable->getChanges()['start_time'])->format('Y-m-d H.i');
        } elseif (! is_null($fileable->name)) {
            $fileable->name = $fileable->getOriginal()['name'];
            $newName = $fileable->getChanges()['name'];
        } else {
            return;
        }

        $sharepointService = new SharepointFileService();

        // get the path of the current fileable object (that's why the fileable->name,title is set above)
        $path = $sharepointService->pathForFileableDriveItem($fileable);

        $sharepointGraph = new \App\Services\SharepointGraphService();

        try {
            $driveItem = $sharepointGraph->updateDriveItemByPath($path, [
                // the drive item name property in Sharepoint is always "name"
                'name' => $newName,
            ]);
        } catch (ClientException $e) {
        }
    }
}
