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
    test('does not double-send when the command runs twice inside the same reminder window', function (): void {
        Notification::fake();

        $institution = Institution::factory()->for(Tenant::factory()->create())->create();
        $duty = Duty::factory()->for($institution)->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, ['start_date' => now()->subMonth(), 'end_date' => null]);

        $meeting = Meeting::factory()->create(['start_time' => now()->addHour()]);
        $meeting->institutions()->attach($institution->id);

        // The 30-minute schedule (routes/console.php) reruns well inside the 60-minute
        // reminder window (±30 min around the target), so a steady-state run always
        // re-finds a meeting the previous run already caught — this reproduces that.
        $this->artisan('notifications:meeting-reminders')->assertSuccessful();
        $this->artisan('notifications:meeting-reminders')->assertSuccessful();

        Notification::assertSentTimes(MeetingReminderNotification::class, 1);
        Notification::assertSentTo($user, MeetingReminderNotification::class);
    });

    test('by default reminds 24 hours and 1 hour before, and never about a deleted meeting', function (): void {
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

        Notification::assertSentTimes(MeetingReminderNotification::class, 2);
        Notification::assertSentTo($user, MeetingReminderNotification::class, $remindedAbout($dayAhead));
        Notification::assertSentTo($user, MeetingReminderNotification::class, $remindedAbout($hourAhead));
    });
});
