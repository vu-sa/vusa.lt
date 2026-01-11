<?php

namespace App\Console;

use App\Jobs\SyncFileableFilesJob;
use App\Jobs\SyncStaleDocumentsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Daily SharePoint document sync - runs at 2 AM to avoid peak usage
        $schedule->job(new SyncStaleDocumentsJob)
            ->dailyAt('02:00')
            ->name('sync-stale-documents')
            ->withoutOverlapping(30); // Prevent overlapping runs, timeout after 30 minutes

        // Weekly sync of FileableFiles to detect externally deleted files
        $schedule->job(new SyncFileableFilesJob)
            ->weeklyOn(1, '03:00') // Monday at 3 AM
            ->name('sync-fileable-files')
            ->withoutOverlapping(60);

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(3);
        // });

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(2);
        // });

        // $schedule->call(function () {
        //     \App\Actions\Schedulable\TaskNotifier::notifyDaysLeft(1);
        // });
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
