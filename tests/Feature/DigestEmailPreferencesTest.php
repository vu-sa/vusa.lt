<?php

use App\Console\Commands\ProcessNotificationDigests;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
    ]);
});

describe('getAvailableDigestEmails', function () {
    test('returns user email when user has no duties', function () {
        $emails = $this->user->getAvailableDigestEmails();

        expect($emails)->toHaveCount(1);
        expect($emails[0]['email'])->toBe('user@example.com');
        expect($emails[0]['type'])->toBe('user');
    });

    test('returns user email and duty emails when user has active duties', function () {
        // Create institution and duty with email
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        // Attach duty to user (active)
        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $emails = $this->user->getAvailableDigestEmails();

        expect($emails)->toHaveCount(2);
        expect(collect($emails)->pluck('email')->toArray())->toContain('user@example.com');
        expect(collect($emails)->pluck('email')->toArray())->toContain('duty@vusa.lt');
    });

    test('returns all duty emails when user has multiple active duties', function () {
        $institution1 = Institution::factory()->for($this->tenant)->create();
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $duty1 = Duty::factory()->for($institution1)->create([
            'email' => 'duty1@vusa.lt',
        ]);
        $duty2 = Duty::factory()->for($institution2)->create([
            'email' => 'duty2@vusa.lt',
        ]);

        $this->user->duties()->attach($duty1->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
        $this->user->duties()->attach($duty2->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $emails = $this->user->getAvailableDigestEmails();

        expect($emails)->toHaveCount(3); // user email + 2 duty emails
        expect(collect($emails)->pluck('email')->toArray())->toContain('user@example.com');
        expect(collect($emails)->pluck('email')->toArray())->toContain('duty1@vusa.lt');
        expect(collect($emails)->pluck('email')->toArray())->toContain('duty2@vusa.lt');
    });

    test('does not include emails from ended duties', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'ended-duty@vusa.lt',
        ]);

        // Attach ended duty
        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        $emails = $this->user->getAvailableDigestEmails();

        expect($emails)->toHaveCount(1);
        expect($emails[0]['email'])->toBe('user@example.com');
    });
});

describe('getDigestEmails', function () {
    test('returns duty email by default when user has @vusa.lt duty email', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $emails = $this->user->getDigestEmails();

        expect($emails)->toBe(['duty@vusa.lt']);
    });

    test('returns first @vusa.lt duty email by default when user has multiple duties', function () {
        $institution1 = Institution::factory()->for($this->tenant)->create();
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $duty1 = Duty::factory()->for($institution1)->create([
            'email' => 'duty1@vusa.lt',
        ]);
        $duty2 = Duty::factory()->for($institution2)->create([
            'email' => 'duty2@vusa.lt',
        ]);

        $this->user->duties()->attach($duty1->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
        $this->user->duties()->attach($duty2->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $emails = $this->user->getDigestEmails();

        // By default, returns only the first @vusa.lt duty email (matches old behavior)
        expect($emails)->toHaveCount(1);
        expect($emails[0])->toEndWith('@vusa.lt');
    });

    test('user can configure multiple duty emails when they have multiple duties', function () {
        $institution1 = Institution::factory()->for($this->tenant)->create();
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $duty1 = Duty::factory()->for($institution1)->create([
            'email' => 'duty1@vusa.lt',
        ]);
        $duty2 = Duty::factory()->for($institution2)->create([
            'email' => 'duty2@vusa.lt',
        ]);

        $this->user->duties()->attach($duty1->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
        $this->user->duties()->attach($duty2->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // User explicitly configures both duty emails
        $this->user->setDigestEmails(['duty1@vusa.lt', 'duty2@vusa.lt']);

        $emails = $this->user->getDigestEmails();

        expect($emails)->toHaveCount(2);
        expect($emails)->toContain('duty1@vusa.lt');
        expect($emails)->toContain('duty2@vusa.lt');
    });

    test('returns user email by default when no duty email exists', function () {
        $emails = $this->user->getDigestEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('returns configured emails when user has set preferences', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use user email instead of duty email
        $this->user->setDigestEmails(['user@example.com']);

        $emails = $this->user->getDigestEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('returns multiple emails when configured', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use both emails
        $this->user->setDigestEmails(['user@example.com', 'duty@vusa.lt']);

        $emails = $this->user->getDigestEmails();

        expect($emails)->toHaveCount(2);
        expect($emails)->toContain('user@example.com');
        expect($emails)->toContain('duty@vusa.lt');
    });

    test('lazy cleanup removes invalid emails and falls back to user email', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        // Attach duty as active
        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use duty email
        $this->user->setDigestEmails(['duty@vusa.lt']);

        // Now end the duty (simulating duty expiration)
        $this->user->duties()->updateExistingPivot($duty->id, [
            'end_date' => now()->subDay(),
        ]);

        // Refresh user to clear cache
        $this->user->refresh();

        // Should fall back to user email since duty email is no longer available
        $emails = $this->user->getDigestEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('lazy cleanup keeps valid emails and removes invalid ones', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use both emails
        $this->user->setDigestEmails(['user@example.com', 'duty@vusa.lt']);

        // End the duty
        $this->user->duties()->updateExistingPivot($duty->id, [
            'end_date' => now()->subDay(),
        ]);

        $this->user->refresh();

        // Should only return user email now
        $emails = $this->user->getDigestEmails();

        expect($emails)->toBe(['user@example.com']);
    });
});

describe('setDigestEmails', function () {
    test('only stores valid emails', function () {
        // Try to set an email that is not available
        $this->user->setDigestEmails(['invalid@notavailable.com']);

        $preferences = $this->user->notification_preferences;

        expect($preferences['digest_emails'])->toBe([]);
    });

    test('stores valid emails', function () {
        $this->user->setDigestEmails(['user@example.com']);

        $preferences = $this->user->notification_preferences;

        expect($preferences['digest_emails'])->toBe(['user@example.com']);
    });
});

describe('updateNotificationPreferences endpoint', function () {
    test('user can update digest emails through API', function () {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'digest_emails' => ['user@example.com'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->notification_preferences['digest_emails'])->toBe(['user@example.com']);
    });

    test('API rejects invalid emails', function () {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'digest_emails' => ['not-an-email'],
            ])
            ->assertSessionHasErrors('digest_emails.0');
    });

    test('API only stores available emails', function () {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'digest_emails' => ['user@example.com', 'notavailable@other.com'],
            ])
            ->assertRedirect();

        $this->user->refresh();
        // Only the available email should be stored
        expect($this->user->notification_preferences['digest_emails'])->toBe(['user@example.com']);
    });
});

describe('userSettings page', function () {
    test('includes availableDigestEmails in props', function () {
        asUser($this->user)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('availableDigestEmails')
                ->where('availableDigestEmails.0.email', 'user@example.com')
                ->where('availableDigestEmails.0.type', 'user')
            );
    });

    test('includes digest_emails in notification preferences', function () {
        $this->user->setDigestEmails(['user@example.com']);

        asUser($this->user)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('notificationPreferences.digest_emails')
                ->where('notificationPreferences.digest_emails', ['user@example.com'])
            );
    });
});

describe('ProcessNotificationDigests command', function () {
    test('sends digest to configured email addresses', function () {
        Mail::fake();

        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Configure to send to user email instead of default duty email
        $this->user->setDigestEmails(['user@example.com']);
        $this->user->setDigestFrequencyHours(1);

        // Create a digest queue item and manually set created_at (not fillable)
        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
            'category' => 'task',
            'data' => [
                'title' => 'Test Notification',
                'body' => 'Test body',
                'url' => 'https://example.com',
                'icon' => 'task',
            ],
        ]);
        $item->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        // Run the command
        $this->artisan(ProcessNotificationDigests::class)
            ->assertSuccessful();

        Mail::assertQueued(NotificationDigest::class, function ($mail) {
            return $mail->hasTo('user@example.com');
        });
    });

    test('sends digest to default duty email when no preference set', function () {
        Mail::fake();

        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $this->user->setDigestFrequencyHours(1);

        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
            'category' => 'task',
            'data' => [
                'title' => 'Test Notification',
                'body' => 'Test body',
                'url' => 'https://example.com',
                'icon' => 'task',
            ],
        ]);
        $item->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        $this->artisan(ProcessNotificationDigests::class)
            ->assertSuccessful();

        Mail::assertQueued(NotificationDigest::class, function ($mail) {
            return $mail->hasTo('duty@vusa.lt');
        });
    });

    test('sends digest to multiple configured emails', function () {
        Mail::fake();

        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Configure to send to both emails
        $this->user->setDigestEmails(['user@example.com', 'duty@vusa.lt']);
        $this->user->setDigestFrequencyHours(1);

        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
            'category' => 'task',
            'data' => [
                'title' => 'Test Notification',
                'body' => 'Test body',
                'url' => 'https://example.com',
                'icon' => 'task',
            ],
        ]);
        $item->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        $this->artisan(ProcessNotificationDigests::class)
            ->assertSuccessful();

        Mail::assertQueued(NotificationDigest::class, function ($mail) {
            return $mail->hasTo('user@example.com') && $mail->hasTo('duty@vusa.lt');
        });
    });
});
