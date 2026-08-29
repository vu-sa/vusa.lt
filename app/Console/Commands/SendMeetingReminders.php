<?php

namespace App\Console\Commands;

use App\Actions\GetInstitutionAdministrators;
use App\Actions\GetInstitutionMembers;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Send meeting reminder notifications based on user preferences.
 *
 * Default reminder hours: 24 and 1 hour before meeting.
 * Users can customize their reminder hours in notification preferences.
 */
#[Description('Send meeting reminder notifications to participants')]
#[Signature('notifications:meeting-reminders')]
class SendMeetingReminders extends Command
{
    public function handle(): int
    {
        $checkHours = $this->getConfiguredReminderHours();

        $sentCount = 0;

        foreach ($checkHours as $hoursAhead) {
            $meetings = $this->getMeetingsInTimeWindow($hoursAhead);

            foreach ($meetings as $meeting) {
                $participants = $this->getMeetingParticipants($meeting);

                foreach ($participants as $user) {
                    // Check if user wants reminders at this hour interval
                    $userReminderHours = $user->getMeetingReminderHours();

                    if (in_array($hoursAhead, $userReminderHours, true)) {
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Meeting>
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
     * @return array<int, int>
     */
    protected function getConfiguredReminderHours(): array
    {
        return User::query()
            ->get()
            ->flatMap(fn (User $user): array => $user->getMeetingReminderHours())
            ->map(fn ($hours): int => (int) $hours)
            ->filter(fn (int $hours): bool => $hours > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Get users who should receive reminders for a meeting: everyone holding a duty in
     * its institutions on the meeting's own date, plus the nominated administrators.
     *
     * This used to flatMap `$duty->users`, i.e. every person who had ever held a duty
     * in the institution, with no date filter at all — a body with a decade of
     * turnover reminded all of them. The breadth (every duty, not just student-rep
     * ones) is deliberate: the chair should still be reminded of their own sitting.
     *
     * @return Collection<int, User>
     */
    protected function getMeetingParticipants(Meeting $meeting): Collection
    {
        return GetInstitutionMembers::forMeeting($meeting)
            ->merge(GetInstitutionAdministrators::forMeeting($meeting))
            ->unique('id')
            ->values();
    }
}
