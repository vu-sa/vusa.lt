<?php

use App\Models\Duty;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new NotificationRouter;
    $this->tenant = Tenant::query()->first();
});

describe('NotificationRouter', function (): void {
    describe('routeForMail', function (): void {
        test('returns user email when no duties exist', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe(['user@example.com']);
        });

        test('returns duty email when it ends with vusa.lt', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty = Duty::factory()->create([
                'email' => 'duty@vusa.lt',
            ]);
            $user->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe(['duty@vusa.lt']);
        });

        test('uses a duty address on another domain rather than the personal one', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty = Duty::factory()->create([
                'email' => 'duty@example.com',
            ]);
            $user->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe(['duty@example.com']);
        });

        test('prefers first vusa.lt duty email when multiple duties exist', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty1 = Duty::factory()->create([
                'email' => 'first@vusa.lt',
            ]);
            $duty2 = Duty::factory()->create([
                'email' => 'second@vusa.lt',
            ]);
            $user->duties()->attach($duty1->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);
            $user->duties()->attach($duty2->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe(['first@vusa.lt']);
        });

        test('prefers a vusa.lt duty address over another duty address', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $other = Duty::factory()->create(['email' => 'role@example.com']);
            $vusa = Duty::factory()->create(['email' => 'role@gmc.vusa.lt']);
            $user->duties()->attach($other->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);
            $user->duties()->attach($vusa->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            expect($this->service->routeForMail($user, new Notification))->toBe(['role@gmc.vusa.lt']);
        });

        test('follows the addresses chosen on the settings page', function (): void {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty = Duty::factory()->create(['email' => 'duty@vusa.lt']);
            $user->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);
            $user->update(['notification_preferences' => ['emails' => ['user@example.com']]]);

            expect($this->service->routeForMail($user->fresh(), new Notification))->toBe(['user@example.com']);
        });
    });
});
