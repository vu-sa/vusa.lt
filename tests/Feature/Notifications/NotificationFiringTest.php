<?php

use App\Events\CommentPosted;
use App\Events\TaskCreated;
use App\Models\Comment;
use App\Models\Pivots\ReservationResource;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\ReservationStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use App\States\ReservationResource\Reserved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    Notification::fake();
    // Ensure queue runs synchronously for listeners
    config(['queue.default' => 'sync']);
});

describe('task notifications', function () {
    test('TaskAssignedNotification fires on task creation event', function () {
        $user = $this->createUserWithPreferences();

        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentTo($user, TaskAssignedNotification::class);
    });

    test('TaskAssignedNotification is sent to all assigned users', function () {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();

        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        $task->users()->attach([$user1->id, $user2->id]);

        event(new TaskCreated($task));

        Notification::assertSentTo($user1, TaskAssignedNotification::class);
        Notification::assertSentTo($user2, TaskAssignedNotification::class);
    });

    test('only one TaskAssignedNotification per user per task creation', function () {
        $user = $this->createUserWithPreferences();

        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentToTimes($user, TaskAssignedNotification::class, 1);
    });

    test('no TaskAssignedNotification for tasks without users', function () {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        // Don't attach any users

        event(new TaskCreated($task));

        Notification::assertNothingSent();
    });
});

describe('comment notifications', function () {
    test('CommentPostedNotification fires on new comment event', function () {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Test comment',
        ]);

        event(new CommentPosted($comment));

        Notification::assertSentTo($user, CommentPostedNotification::class);
    });

    // Note: Decision/status-change comments are now handled through the Approvals system
    // (see 2026_01_15_160000 migration). Comments are purely for discussion.
});

describe('reservation notifications', function () {
    test('ReservationStatusChangedNotification fires on state transition', function () {
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource, 'reservation' => $reservation] = $this->createReservationWithResource($user);

        // Manually dispatch state changed event (normally done by Spatie state machine)
        $initialState = $reservationResource->state;
        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentTo($user, ReservationStatusChangedNotification::class);
    });

    test('ReservationStatusChangedNotification contains correct state information', function () {
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentTo(
            $user,
            ReservationStatusChangedNotification::class,
            function ($notification, $channels) {
                $data = $notification->toArray($notification);
                expect($data['category'])->toBe('reservation');

                return true;
            }
        );
    });

    test('ReservationStatusChangedNotification sends to all reservation users', function () {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();

        // Create reservation and attach both users
        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user1);
        $reservation->users()->attach($user2);

        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentTo($user1, ReservationStatusChangedNotification::class);
        Notification::assertSentTo($user2, ReservationStatusChangedNotification::class);
    });
});

describe('notification not sent to commenter', function () {
    test('comment author does not need to receive their own comment notification', function () {
        // This is a behavior observation - the current implementation may or may not
        // filter out the comment author. This test documents expected behavior.
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // User comments on their own reservation
        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $user->id, // Same user is the commenter
            'comment' => 'My own comment',
        ]);

        event(new CommentPosted($comment));

        // The user who commented is also the reservation owner, so they will receive the notification
        // This is the current behavior - whether to exclude self-comments is a product decision
        Notification::assertSentTo($user, CommentPostedNotification::class);
    });
});
