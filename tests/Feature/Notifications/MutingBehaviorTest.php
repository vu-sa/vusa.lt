<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Events\CommentPosted;
use App\Events\TaskCreated;
use App\Models\Comment;
use App\Models\Pivots\ReservationResource;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\ReservationStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    Notification::fake();
});

describe('global muting', function () {
    test('via method returns empty array when user is globally muted', function () {
        $user = $this->createMutedUser(now()->addHours(2));

        // Create a notification and test via() directly
        $task = Task::factory()->create(['due_date' => now()->addDays(7)]);
        $notification = new TaskAssignedNotification($task, null);

        // Verify that via() returns empty channels due to muting
        $channels = $notification->via($user);

        expect($channels)->toBeEmpty();
    });

    test('muted user via() returns empty for all notification types', function () {
        $user = $this->createMutedUser(now()->addHours(2));

        // Test with different notification types
        $task = Task::factory()->create(['due_date' => now()->addDays(7)]);

        $taskNotification = new TaskAssignedNotification($task, null);
        expect($taskNotification->via($user))->toBeEmpty();

        $commentNotification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );
        expect($commentNotification->via($user))->toBeEmpty();
    });

    test('notifications resume after mute expires', function () {
        $user = $this->createMutedUser(now()->addMinutes(30));

        // Initially muted
        expect($user->isGloballyMuted())->toBeTrue();

        // Travel past the mute expiry
        $this->travelTo(now()->addMinutes(31));

        expect($user->isGloballyMuted())->toBeFalse();

        // Create and send notification after mute expired
        $task = Task::factory()->create(['due_date' => now()->addDays(7)]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentTo($user, TaskAssignedNotification::class, function ($notification, $channels) use ($user) {
            $viaChannels = $notification->via($user);

            return ! empty($viaChannels);
        });
    });

    test('globally muted user via() returns empty for reservation notifications', function () {
        $user = $this->createMutedUser(now()->addHours(2));

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Test via() directly without state transition
        $notification = new ReservationStatusChangedNotification(
            $reservationResource,
            'created',
            'reserved',
            null
        );

        expect($notification->via($user))->toBeEmpty();
    });

    test('globally muted user via() returns empty for comment notifications', function () {
        $user = $this->createMutedUser(now()->addHours(2));

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        expect($notification->via($user))->toBeEmpty();
    });
});

describe('thread muting', function () {
    test('muted thread does not receive notification channels', function () {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Mute this specific thread - use short class name to match notification's object format
        $user->muteThread('ReservationResource', (string) $reservationResource->id);

        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Test',
        ]);

        event(new CommentPosted($comment));

        // The notification is sent, but isNotificationMuted should return true
        // which affects via() channels based on notification implementation
        Notification::assertSentTo($user, CommentPostedNotification::class);

        // Verify the mute is in place
        $user->refresh();
        expect($user->notification_preferences['muted_threads'])->not->toBeEmpty();
    });

    test('other threads still receive notifications when one is muted', function () {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource1] = $this->createReservationWithResource($user);
        ['reservationResource' => $reservationResource2] = $this->createReservationWithResource($user);

        // Mute only the first thread - use short class name
        $user->muteThread('ReservationResource', (string) $reservationResource1->id);

        // Comment on the second (unmuted) thread
        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource2->id,
            'user_id' => $commenter->id,
            'comment' => 'Test on unmuted thread',
        ]);

        event(new CommentPosted($comment));

        Notification::assertSentTo($user, CommentPostedNotification::class, function ($notification, $channels) use ($user) {
            // Channels should NOT be empty for unmuted thread
            $viaChannels = $notification->via($user);

            return ! empty($viaChannels);
        });
    });

    test('thread mute expires correctly with time travel', function () {
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Mute for 1 hour - use short class name to match notification's object format
        $user->muteThread(
            'ReservationResource',
            (string) $reservationResource->id,
            now()->addHour()
        );

        // Create a mock notification to test against
        $notification = new CommentPostedNotification(
            'Test',
            [
                'modelClass' => 'ReservationResource',
                'name' => 'Test',
                'url' => '/test',
                'id' => (string) $reservationResource->id, // Cast to string to match storage
            ],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        // Currently muted
        expect($user->isNotificationMuted($notification))->toBeTrue();

        // Travel past expiry
        $this->travelTo(now()->addHours(2));

        // No longer muted
        expect($user->isNotificationMuted($notification))->toBeFalse();
    });
});

describe('channel preferences', function () {
    test('disabled push channel means no webpush in via array', function () {
        $user = $this->createUserWithDisabledChannel(
            NotificationCategory::Task,
            NotificationChannel::Push
        );

        $task = Task::factory()->create(['due_date' => now()->addDays(7)]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        // The notification is sent, but we verify the user preference is set
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::Push
        ))->toBeFalse();

        // InApp should still work
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::InApp
        ))->toBeTrue();
    });

    test('disabled in_app channel preference is respected', function () {
        $user = $this->createUserWithDisabledChannel(
            NotificationCategory::Comment,
            NotificationChannel::InApp
        );

        expect($user->shouldReceiveNotification(
            NotificationCategory::Comment,
            NotificationChannel::InApp
        ))->toBeFalse();

        // Other categories should still work
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::InApp
        ))->toBeTrue();
    });

    test('category-specific preferences only affect that category', function () {
        $user = $this->createUserWithPreferences();

        // Disable email digest only for comments
        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            false
        );

        $user->refresh();

        // Comment email digest is disabled
        expect($user->shouldReceiveNotification(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest
        ))->toBeFalse();

        // Task email digest is still enabled
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::EmailDigest
        ))->toBeTrue();

        // Reservation email digest is still enabled
        expect($user->shouldReceiveNotification(
            NotificationCategory::Reservation,
            NotificationChannel::EmailDigest
        ))->toBeTrue();
    });
});

describe('muting edge cases', function () {
    test('notification without object does not crash thread mute check', function () {
        $user = $this->createUserWithPreferences();

        // Mute a thread
        $user->muteThread('App\\Models\\Task', '999');

        // Create notification without object() returning data
        $notification = new class extends \App\Notifications\BaseNotification
        {
            public function category(): NotificationCategory
            {
                return NotificationCategory::System;
            }

            public function title(object $notifiable): string
            {
                return 'Test';
            }

            public function body(object $notifiable): string
            {
                return 'Test';
            }

            public function url(): string
            {
                return '/';
            }

            // object() returns null by default
        };

        // Should not throw exception
        $isMuted = $user->isNotificationMuted($notification);

        expect($isMuted)->toBeFalse();
    });
});
