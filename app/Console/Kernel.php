<?php

namespace App\Console;

use App\Actions\Schedulable\TaskNotifier;
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

        // =====================================================================
        // NOTIFICATION SCHEDULING
        // =====================================================================

        // Process notification digests - runs hourly to check user preferences
        $schedule->command('notifications:send-digests')
            ->hourly()
            ->name('notification-digests')
            ->withoutOverlapping(10);

        // Task reminders - runs daily at 8 AM for tasks due in 7, 3, or 1 days
        $schedule->call(function () {
            // These reminder days are defaults; users can customize in preferences
            TaskNotifier::notifyDaysLeft(7);
            TaskNotifier::notifyDaysLeft(3);
            TaskNotifier::notifyDaysLeft(1);
        })->dailyAt('08:00')
            ->name('task-reminders');

        // Meeting reminders - runs every 30 minutes to catch all reminder windows
        $schedule->command('notifications:meeting-reminders')
            ->everyThirtyMinutes()
            ->name('meeting-reminders')
            ->withoutOverlapping(5);

        // Duty expiry reminders - runs daily at 9 AM (30 days before end)
        $schedule->command('notifications:duty-expiry-reminders')
            ->dailyAt('09:00')
            ->name('duty-expiry-reminders');

        // Task overdue reminders - runs weekly on Monday at 9 AM
        $schedule->command('notifications:task-overdue-reminders')
            ->weeklyOn(1, '09:00')
            ->name('task-overdue-reminders');

        // Periodicity gap tasks - runs daily at 8 AM to create tasks for
        // institutions approaching their meeting periodicity threshold
        $schedule->command('tasks:repopulate institution --force')
            ->dailyAt('08:00')
            ->name('periodicity-gap-tasks')
            ->withoutOverlapping(15);
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
