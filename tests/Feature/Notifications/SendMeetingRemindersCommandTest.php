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
});
