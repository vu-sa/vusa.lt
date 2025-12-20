<?php

namespace App\Listeners;

use App\Events\FileableNameUpdated;
use App\Services\ResourceServices\SharepointFileService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        // Skip SharePoint operations in testing environment
        if (app()->environment('testing')) {
            return;
        }

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

        $sharepointService = new SharepointFileService;

        // get the path of the current fileable object (that's why the fileable->name,title is set above). But right now, path depends on the fileable tenant name, and not always this name is present
        try {
            $path = $sharepointService->pathForFileableDriveItem($fileable);
        } catch (HttpException $e) {
            report($e);

            return;
        }

        $sharepointGraph = new \App\Services\SharepointGraphService(driveId: config('filesystems.sharepoint.vusa_drive_id'));

        // Update the drive item - errors are handled silently within the service
        $driveItem = $sharepointGraph->updateDriveItemByPath($path, [
            // the drive item name property in Sharepoint is always "name"
            'name' => $newName,
        ]);

        if ($driveItem === null) {
            // Log for monitoring but continue gracefully
            Log::warning('SharePoint folder update failed', [
                'path' => $path,
                'new_name' => $newName,
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->id ?? null,
            ]);
        }
    }
}
