<?php

use App\Models\Duty;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new NotificationRouter;
    $this->tenant = Tenant::query()->first();
});

describe('NotificationRouter', function () {
    describe('routeForMail', function () {
        test('returns user email when no duties exist', function () {
            $user = User::factory()->create(['email' => 'user@example.com']);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe('user@example.com');
        });

        test('returns duty email when it ends with vusa.lt', function () {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty = Duty::factory()->create([
                'email' => 'duty@vusa.lt',
            ]);
            $user->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe('duty@vusa.lt');
        });

        test('falls back to user email when duty email does not end with vusa.lt', function () {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $duty = Duty::factory()->create([
                'email' => 'duty@example.com',
            ]);
            $user->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->addYear()]);

            $result = $this->service->routeForMail($user, new Notification);

            expect($result)->toBe('user@example.com');
        });

        test('prefers first vusa.lt duty email when multiple duties exist', function () {
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

            expect($result)->toBe('first@vusa.lt');
        });
    });
});
