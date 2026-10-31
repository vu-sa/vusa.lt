<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

describe('notifications:meeting-reminders', function (): void {
    test('opting in to one hour sends once when the command repeats in the same window', function (): void {
        Notification::fake();

        $institution = Institution::factory()->for(Tenant::factory()->create())->create();
        $duty = Duty::factory()->for($institution)->create();

        $user = User::factory()->create(['notification_preferences' => ['reminder_settings' => ['meeting_reminder_hours' => [1]]]]);
        $user->duties()->attach($duty, ['start_date' => now()->subMonth(), 'end_date' => null]);

        $meeting = Meeting::factory()->create(['start_time' => now()->addHour()]);
        $meeting->institutions()->attach($institution->id);

        // The half-hour schedule can find the same meeting twice in its one-hour window.
        $this->artisan('notifications:meeting-reminders')->assertSuccessful();
        $this->artisan('notifications:meeting-reminders')->assertSuccessful();

        Notification::assertSentTimes(MeetingReminderNotification::class, 1);
        Notification::assertSentTo($user, MeetingReminderNotification::class);
    });

    test('by default reminds only 24 hours before, and never about a deleted meeting', function (): void {
        Notification::fake();

        $institution = Institution::factory()->for(Tenant::factory()->create())->create();
        $duty = Duty::factory()->for($institution)->create();
        $user = User::factory()->create();
        $user->duties()->attach($duty, ['start_date' => now()->subMonth(), 'end_date' => null]);

        $dayAhead = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addDay()]);
        $hourAhead = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addHour()]);
        Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addHours(12)]);
        Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addHour()])->delete();

        $this->artisan('notifications:meeting-reminders')->assertSuccessful();

        $remindedAbout = fn (Meeting $meeting): Closure => fn (MeetingReminderNotification $notification): bool => $notification->url() === route('meetings.show', $meeting);

        Notification::assertSentTimes(MeetingReminderNotification::class, 1);
        Notification::assertSentTo($user, MeetingReminderNotification::class, $remindedAbout($dayAhead));
        Notification::assertNotSentTo($user, MeetingReminderNotification::class, $remindedAbout($hourAhead));
    });
});
