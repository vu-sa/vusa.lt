<?php

use App\Console\Commands\ProcessNotificationDigests;
use App\Enums\NotificationType;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setTime(12, 0));
    $this->tenant = Tenant::query()->first();
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
    ]);
});

describe('getAvailableNotificationEmails', function (): void {
    test('returns user email when user has no duties', function (): void {
        $emails = $this->user->getAvailableNotificationEmails();

        expect($emails)->toHaveCount(1)
            ->and($emails[0])->toMatchArray(['email' => 'user@example.com', 'type' => 'user']);
    });

    test('returns user email and duty emails when user has active duties', function (): void {
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

        $emails = $this->user->getAvailableNotificationEmails();

        expect($emails)->toHaveCount(2)
            ->and(collect($emails)->pluck('email')->toArray())->toContain('user@example.com')
            ->toContain('duty@vusa.lt');
    });

    test('returns all duty emails when user has multiple active duties', function (): void {
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

        $emails = $this->user->getAvailableNotificationEmails();

        expect($emails)->toHaveCount(3); // user email + 2 duty emails
        expect(collect($emails)->pluck('email')->toArray())->toContain('user@example.com');
        expect(collect($emails)->pluck('email')->toArray())->toContain('duty1@vusa.lt')
            ->toContain('duty2@vusa.lt');
    });

    test('does not include emails from ended duties', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'ended-duty@vusa.lt',
        ]);

        // Attach ended duty
        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        $emails = $this->user->getAvailableNotificationEmails();

        expect($emails)->toHaveCount(1)
            ->and($emails[0]['email'])->toBe('user@example.com');
    });
});

describe('notificationEmails', function (): void {
    test('returns duty email by default when user has @vusa.lt duty email', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $emails = $this->user->notificationEmails();

        expect($emails)->toBe(['duty@vusa.lt']);
    });

    test('returns first @vusa.lt duty email by default when user has multiple duties', function (): void {
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

        $emails = $this->user->notificationEmails();

        // By default, returns only the first @vusa.lt duty email (matches old behavior)
        expect($emails)->toHaveCount(1);
        expect($emails[0])->toEndWith('@vusa.lt');
    });

    test('user can configure multiple duty emails when they have multiple duties', function (): void {
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
        $this->user->update(['notification_preferences' => ['emails' => ['duty1@vusa.lt', 'duty2@vusa.lt']]]);

        $emails = $this->user->notificationEmails();

        expect($emails)->toHaveCount(2)
            ->toContain('duty1@vusa.lt')
            ->toContain('duty2@vusa.lt');
    });

    test('returns user email by default when no duty email exists', function (): void {
        $emails = $this->user->notificationEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('returns configured emails when user has set preferences', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use user email instead of duty email
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com']]]);

        $emails = $this->user->notificationEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('returns multiple emails when configured', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use both emails
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com', 'duty@vusa.lt']]]);

        $emails = $this->user->notificationEmails();

        expect($emails)->toHaveCount(2)
            ->toContain('user@example.com')
            ->toContain('duty@vusa.lt');
    });

    test('lazy cleanup removes invalid emails and falls back to the default address', function (): void {
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
        $this->user->update(['notification_preferences' => ['emails' => ['duty@vusa.lt']]]);

        // Now end the duty (simulating duty expiration)
        $this->user->duties()->updateExistingPivot($duty->id, [
            'end_date' => now()->subDay(),
        ]);

        // Refresh user to clear cache
        $this->user->refresh();

        // No current duty address is left, so the default is the personal address.
        $emails = $this->user->notificationEmails();

        expect($emails)->toBe(['user@example.com']);
    });

    test('lazy cleanup keeps valid emails and removes invalid ones', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Set preference to use both emails
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com', 'duty@vusa.lt']]]);

        // End the duty
        $this->user->duties()->updateExistingPivot($duty->id, [
            'end_date' => now()->subDay(),
        ]);

        $this->user->refresh();

        // Should only return user email now
        $emails = $this->user->notificationEmails();

        expect($emails)->toBe(['user@example.com']);
    });
});

describe('updateNotificationPreferences endpoint', function (): void {
    test('followed-institution push is on until the user turns it off', function (): void {
        expect($this->user->wantsPushFor(NotificationType::FollowedInstitutionActivity))->toBeTrue();

        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), ['types' => ['followed_institution_activity' => ['email' => 'digest', 'push' => false]]])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($this->user->refresh()->wantsPushFor(NotificationType::FollowedInstitutionActivity))->toBeFalse();
    });

    test('user can update digest emails through API', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'emails' => ['user@example.com'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->notification_preferences['emails'])->toBe(['user@example.com']);
    });

    test('API rejects invalid emails', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'emails' => ['not-an-email'],
            ])
            ->assertSessionHasErrors('emails.0');
    });

    test('API only stores available emails', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'emails' => ['user@example.com', 'notavailable@other.com'],
            ])
            ->assertRedirect();

        $this->user->refresh();
        // Only the available email should be stored
        expect($this->user->notification_preferences['emails'])->toBe(['user@example.com']);
    });
});

describe('notificationSettings page', function (): void {
    test('includes availableEmails in props', function (): void {
        asUser($this->user)
            ->get(route('profile.notifications'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotificationSettings')
                ->has('availableEmails')
                ->where('availableEmails.0.email', 'user@example.com')
                ->where('availableEmails.0.type', 'user')
            );
    });

    test('includes chosen emails in notification preferences', function (): void {
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com']]]);

        asUser($this->user)
            ->get(route('profile.notifications'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotificationSettings')
                ->has('notificationPreferences.emails')
                ->where('notificationPreferences.emails', ['user@example.com'])
            );
    });
});

describe('ProcessNotificationDigests command', function (): void {
    test('sends digest to configured email addresses', function (): void {
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
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com']]]);
        $this->user->update(['notification_preferences' => [...$this->user->notification_preferences, 'digest_frequency_hours' => 1]]);

        // Create a digest queue item and manually set created_at (not fillable)
        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => TaskAssignedNotification::class,
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

        Mail::assertSent(NotificationDigest::class, fn ($mail) => $mail->hasTo('user@example.com'));
    });

    test('sends digest to default duty email when no preference set', function (): void {
        Mail::fake();

        $institution = Institution::factory()->for($this->tenant)->create();
        $duty = Duty::factory()->for($institution)->create([
            'email' => 'duty@vusa.lt',
        ]);

        $this->user->duties()->attach($duty->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $this->user->update(['notification_preferences' => [...$this->user->notification_preferences, 'digest_frequency_hours' => 1]]);

        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => TaskAssignedNotification::class,
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

        Mail::assertSent(NotificationDigest::class, fn ($mail) => $mail->hasTo('duty@vusa.lt'));
    });

    test('sends digest to multiple configured emails', function (): void {
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
        $this->user->update(['notification_preferences' => ['emails' => ['user@example.com', 'duty@vusa.lt']]]);
        $this->user->update(['notification_preferences' => [...$this->user->notification_preferences, 'digest_frequency_hours' => 1]]);

        $item = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_class' => TaskAssignedNotification::class,
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

        Mail::assertSent(NotificationDigest::class, fn ($mail) => $mail->hasTo('user@example.com') && $mail->hasTo('duty@vusa.lt'));
    });
});
