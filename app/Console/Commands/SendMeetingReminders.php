<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Send meeting reminder notifications based on user preferences.
 *
 * Default reminder hours: 24 and 1 hour before meeting.
 * Users can customize their reminder hours in notification preferences.
 */
class SendMeetingReminders extends Command
{
    protected $signature = 'notifications:meeting-reminders';

    protected $description = 'Send meeting reminder notifications to participants';

    public function handle(): int
    {
        // Get default reminder hours (these are the checkpoints we run)
        $checkHours = [24, 1];

        $sentCount = 0;

        foreach ($checkHours as $hoursAhead) {
            $meetings = $this->getMeetingsInTimeWindow($hoursAhead);

            foreach ($meetings as $meeting) {
                $participants = $this->getMeetingParticipants($meeting);

                foreach ($participants as $user) {
                    // Check if user wants reminders at this hour interval
                    $userReminderHours = $user->getMeetingReminderHours();

                    if (in_array($hoursAhead, $userReminderHours)) {
                        $user->notify(new MeetingReminderNotification($meeting, $hoursAhead));
                        $sentCount++;
                    }
                }
            }
        }

        $this->info("Sent {$sentCount} meeting reminder notifications.");

        return self::SUCCESS;
    }

    /**
     * Get meetings that start within a specific time window.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting>
     */
    protected function getMeetingsInTimeWindow(int $hoursAhead): \Illuminate\Database\Eloquent\Collection
    {
        $targetTime = Carbon::now()->addHours($hoursAhead);

        // Window of 30 minutes before and after target time
        $windowStart = $targetTime->copy()->subMinutes(30);
        $windowEnd = $targetTime->copy()->addMinutes(30);

        return Meeting::query()
            ->with(['institutions'])
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->get();
    }

    /**
     * Get users who should receive reminders for a meeting.
     */
    protected function getMeetingParticipants(Meeting $meeting): \Illuminate\Support\Collection
    {
        // Get users attached to the meeting's institutions
        $users = collect();

        foreach ($meeting->institutions as $institution) {
            $institutionUsers = $institution->duties()
                ->with('users')
                ->get()
                ->flatMap(fn (\App\Models\Duty $duty) => $duty->users);

            $users = $users->merge($institutionUsers);
        }

        return $users->unique('id');
    }
}
