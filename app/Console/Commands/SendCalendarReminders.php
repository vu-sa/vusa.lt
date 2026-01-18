<?php

namespace App\Console\Commands;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Calendar;
use App\Models\User;
use App\Notifications\CalendarReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Send calendar reminder notifications based on user preferences.
 *
 * Default reminder hours: 24 hours before event (next day reminder).
 * Users can customize their reminder hours in notification preferences.
 * This notification is opt-in (disabled by default).
 *
 * Deduplication: Uses cache to ensure each user receives only one
 * notification per event per reminder interval.
 */
class SendCalendarReminders extends Command
{
    protected $signature = 'notifications:calendar-reminders';

    protected $description = 'Send calendar reminder notifications for upcoming events';

    public function handle(): int
    {
        // Check hours we want to send reminders for
        $checkHours = [24, 1];

        $sentCount = 0;

        foreach ($checkHours as $hoursAhead) {
            $events = $this->getEventsInTimeWindow($hoursAhead);

            if ($events->isEmpty()) {
                continue;
            }

            // Get users who have opted in to calendar notifications
            $usersToNotify = $this->getUsersOptedInForCalendar();

            foreach ($events as $event) {
                foreach ($usersToNotify as $user) {
                    // Check if user wants reminders at this hour interval
                    $userReminderHours = $user->getCalendarReminderHours();

                    if (in_array($hoursAhead, $userReminderHours)) {
                        // Check if we already sent this reminder (deduplication)
                        $cacheKey = "calendar_reminder:{$event->id}:{$user->id}:{$hoursAhead}";

                        if (Cache::has($cacheKey)) {
                            continue;
                        }

                        $user->notify(new CalendarReminderNotification($event, $hoursAhead));
                        $sentCount++;

                        // Cache for 2 hours to prevent duplicates
                        Cache::put($cacheKey, true, now()->addHours(2));
                    }
                }
            }
        }

        $this->info("Sent {$sentCount} calendar reminder notifications.");

        return self::SUCCESS;
    }

    /**
     * Get calendar events that start within a specific time window.
     */
    protected function getEventsInTimeWindow(int $hoursAhead): \Illuminate\Database\Eloquent\Collection
    {
        $targetTime = Carbon::now()->addHours($hoursAhead);

        // Window of 30 minutes before and after target time
        $windowStart = $targetTime->copy()->subMinutes(30);
        $windowEnd = $targetTime->copy()->addMinutes(30);

        return Calendar::query()
            ->with('tenant')
            ->where('is_draft', false)
            ->whereBetween('date', [$windowStart, $windowEnd])
            ->get();
    }

    /**
     * Get users who have opted in to calendar notifications.
     */
    protected function getUsersOptedInForCalendar(): \Illuminate\Support\Collection
    {
        return User::all()->filter(function (User $user) {
            // Check if user has enabled at least one channel for calendar
            return $user->shouldReceiveNotification(NotificationCategory::Calendar, NotificationChannel::InApp)
                || $user->shouldReceiveNotification(NotificationCategory::Calendar, NotificationChannel::Push);
        });
    }
}
