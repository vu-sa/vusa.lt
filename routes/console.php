<?php

use App\Actions\Schedulable\TaskNotifier;
use App\Jobs\SyncFileableFilesJob;
use App\Jobs\SyncStaleDocumentsJob;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands and scheduled tasks. Each Closure is bound to a command
| instance allowing a simple approach to interacting with each
| command's IO methods.
|
*/

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Daily SharePoint document sync - runs at 2 AM to avoid peak usage
Schedule::job(new SyncStaleDocumentsJob)
    ->dailyAt('02:00')
    ->name('sync-stale-documents')
    ->withoutOverlapping(30); // Prevent overlapping runs, timeout after 30 minutes

// Weekly sync of FileableFiles to detect externally deleted files
Schedule::job(new SyncFileableFilesJob)
    ->weeklyOn(1, '03:00') // Monday at 3 AM
    ->name('sync-fileable-files')
    ->withoutOverlapping(60);

// =====================================================================
// NOTIFICATION SCHEDULING
// =====================================================================

// Process notification digests - runs hourly to check user preferences
Schedule::command('notifications:send-digests')
    ->hourly()
    ->name('notification-digests')
    ->withoutOverlapping(10);

// Task reminders - runs daily at 8 AM for tasks due in 7, 3, or 1 days
Schedule::call(function (): void {
    // These reminder days are defaults; users can customize in preferences
    TaskNotifier::notifyDaysLeft(7);
    TaskNotifier::notifyDaysLeft(3);
    TaskNotifier::notifyDaysLeft(1);
})->dailyAt('08:00')
    ->name('task-reminders');

// Meeting reminders - runs every 30 minutes to catch all reminder windows
Schedule::command('notifications:meeting-reminders')
    ->everyThirtyMinutes()
    ->name('meeting-reminders')
    ->withoutOverlapping(5);

// Duty expiry reminders - runs daily at 9 AM (30 days before end)
Schedule::command('notifications:duty-expiry-reminders')
    ->dailyAt('09:00')
    ->name('duty-expiry-reminders');

// Task overdue reminders - runs weekly on Monday at 9 AM
Schedule::command('notifications:task-overdue-reminders')
    ->weeklyOn(1, '09:00')
    ->name('task-overdue-reminders');

// Periodicity gap tasks - runs daily at 8 AM to create tasks for
// institutions approaching their meeting periodicity threshold
Schedule::command('tasks:repopulate institution --force')
    ->dailyAt('08:00')
    ->name('periodicity-gap-tasks')
    ->withoutOverlapping(15);

// News notifications - runs every 15 minutes to check for newly published news
// Notifications are opt-in (disabled by default)
Schedule::command('notifications:send-news')
    ->everyFifteenMinutes()
    ->name('news-notifications')
    ->withoutOverlapping(5);

// Calendar reminders - runs every 30 minutes to catch all reminder windows
// Notifications are opt-in (disabled by default)
Schedule::command('notifications:calendar-reminders')
    ->everyThirtyMinutes()
    ->name('calendar-reminders')
    ->withoutOverlapping(5);

// =====================================================================
// SEARCH INDEX SYNC
// =====================================================================

// Scheduled news/pages enter the public search index once their publish_time
// passes. The model save hooks only fire on save, so without this nothing
// else would ever index them — see SyncPublicSearchIndex for details.
Schedule::command('search:sync-public')
    ->everyFiveMinutes()
    ->name('sync-public-search-index')
    ->withoutOverlapping(5);

// Prune stale digest items so a stalled mail pipeline cannot build an
// unbounded backlog of notifications nobody will ever want to read.
// The cutoff is deliberately conservative: a shorter one risks deleting
// digests that are merely undelivered rather than genuinely stale.
Schedule::command('notifications:prune-digests --older-than=30 --force')
    ->daily()
    ->name('prune-notification-digests')
    ->withoutOverlapping(10);

// =====================================================================
// BACKUPS & RETENTION
// =====================================================================

// Until now a backup only existed if somebody deployed, so a quiet week left
// the newest dump a week old. Runs before the retention sweeps below so the
// day's backup captures the rows they are about to delete.
Schedule::command('deployment:backup')
    ->dailyAt('01:30')
    ->name('database-backup')
    ->withoutOverlapping(60);

// Telescope was 53% of the production database (334 MB) and 92% of staging's
// (1.5 GB) because nothing ever pruned it, on a disk that is 86% full.
Schedule::command('telescope:prune --hours=168')
    ->daily()
    ->name('prune-telescope')
    ->withoutOverlapping(30);

// Retention comes from config/activitylog.php (clean_after_days), which was
// already set to 365 — the command was simply never scheduled.
Schedule::command('activitylog:clean')
    ->weeklyOn(0, '03:30')
    ->name('clean-activity-log')
    ->withoutOverlapping(60);

// Staging tracks production's data as well as its code. The command refuses to
// run outside APP_ENV=staging on its own, but scheduling it only there keeps it
// off production's schedule:list entirely. Runs after the 01:30 backup so it
// picks up the same night's dump rather than yesterday's.
if (config('app.env') === 'staging') {
    Schedule::command('staging:refresh-database')
        ->dailyAt('04:00')
        ->name('staging-database-refresh')
        ->withoutOverlapping(120);
}

// =====================================================================
// SCHEDULER HEARTBEAT
// =====================================================================

// Records that the scheduler ran. SystemMonitorService reads this to tell
// a working scheduler from a dead one — without it, a stopped cron is
// silent, and everything scheduled above simply never happens.
Schedule::call(fn () => Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, now()->toIso8601String()))
    ->everyMinute()
    ->name('scheduler-heartbeat');
