<?php

use App\Jobs\SyncFileableFilesJob;

test('job has a timeout long enough for the full sequential SharePoint sync and runs where it is not redelivered mid-run', function (): void {
    $job = new SyncFileableFilesJob;

    expect($job->timeout)->toBe(1800)
        ->and($job->connection)->toBe('long-running')
        ->and(config('queue.connections.long-running.retry_after'))->toBeGreaterThan($job->timeout);
});
