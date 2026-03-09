<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('tutorial API', function () {
    test('marking first tutorial sends welcome notification', function () {
        Notification::fake();

        $user = User::factory()->create([
            'tutorial_progress' => [],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('api.v1.admin.tutorials.complete'),
            ['tour_id' => 'admin-home-v1']
        );

        $response->assertSuccessful();

        Notification::assertSentTo($user, WelcomeNotification::class);
    });

    test('marking second tutorial does not send welcome notification', function () {
        Notification::fake();

        $user = User::factory()->create([
            'tutorial_progress' => [
                'admin-home-v1' => now()->toIso8601String(),
            ],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('api.v1.admin.tutorials.complete'),
            ['tour_id' => 'atstovavimas-overview-v1']
        );

        $response->assertSuccessful();

        Notification::assertNotSentTo($user, WelcomeNotification::class);
    });

    test('marking same tutorial again does not send notification', function () {
        Notification::fake();

        $user = User::factory()->create([
            'tutorial_progress' => [
                'admin-home-v1' => now()->toIso8601String(),
            ],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('api.v1.admin.tutorials.complete'),
            ['tour_id' => 'admin-home-v1']
        );

        $response->assertSuccessful();

        Notification::assertNotSentTo($user, WelcomeNotification::class);
    });

    test('tutorial completion saves progress', function () {
        Notification::fake();

        $user = User::factory()->create([
            'tutorial_progress' => [],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('api.v1.admin.tutorials.complete'),
            ['tour_id' => 'test-tour-v1']
        );

        $response->assertSuccessful();

        $user->refresh();
        expect($user->tutorial_progress)->toHaveKey('test-tour-v1');
    });
});
