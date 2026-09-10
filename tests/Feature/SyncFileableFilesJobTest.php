<?php

use App\Jobs\SyncFileableFilesJob;

test('job has a timeout long enough for the full sequential SharePoint sync and runs on the single-worker queue', function (): void {
    $job = new SyncFileableFilesJob;

    expect($job->timeout)->toBe(1800)
        ->and($job->queue)->toBe('sharepoint-sync');
});
