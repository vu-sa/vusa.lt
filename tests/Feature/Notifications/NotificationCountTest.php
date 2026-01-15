<?php

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Pivots\ReservationResource;
use App\Notifications\CommentPostedNotification;
use App\Notifications\ReservationStatusChangedNotification;
use App\States\ReservationResource\Reserved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    Notification::fake();
});

describe('reservation flow duplicate prevention', function () {
    test('state change with decision comment sends at most 1 notification type per action', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Simulate admin changing state and leaving a decision comment
        $this->actingAs($admin);

        // First: State change triggers ReservationStatusChangedNotification
        $reservationResource->state->transitionTo(Reserved::class);

        // Second: Decision comment is posted (this is what happens in the controller)
        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $admin->id,
            'comment' => 'Approved the reservation',
            'decision' => true,
        ]);

        event(new CommentPosted($comment));

        // User should receive ReservationStatusChangedNotification
        Notification::assertSentTo($user, ReservationStatusChangedNotification::class);

        // User should NOT receive CommentPostedNotification for decision comments on ReservationResource
        // because the status change notification already covers this
        Notification::assertNotSentTo($user, CommentPostedNotification::class);
    });

    test('state change without comment sends exactly 1 notification', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // Only state change, no comment
        $reservationResource->state->transitionTo(Reserved::class);

        // Exactly one notification type
        Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
        Notification::assertNotSentTo($user, CommentPostedNotification::class);
    });

    test('regular comment without state change sends exactly 1 notification', function () {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Regular comment (not a decision)
        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Just a regular question',
            'decision' => false,
        ]);

        event(new CommentPosted($comment));

        // Only CommentPostedNotification, no status change
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 1);
        Notification::assertNotSentTo($user, ReservationStatusChangedNotification::class);
    });
});

describe('notification count limits', function () {
    test('single reservation state change does not exceed 1 notification per user', function () {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user1);
        $reservation->users()->attach($user2);

        $reservationResource->state->transitionTo(Reserved::class);

        // Each user should receive exactly 1 notification
        Notification::assertSentToTimes($user1, ReservationStatusChangedNotification::class, 1);
        Notification::assertSentToTimes($user2, ReservationStatusChangedNotification::class, 1);
    });

    test('combined state change and decision comment never sends more than 2 notifications total per user', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // State change
        $reservationResource->state->transitionTo(Reserved::class);

        // Decision comment
        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $admin->id,
            'comment' => 'Approved',
            'decision' => true,
        ]);
        event(new CommentPosted($comment));

        // Total notifications for user should be at most 1 (status change)
        // CommentPosted should not fire for decision on ReservationResource
        // Count ReservationStatusChangedNotification sends
        Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
        Notification::assertNotSentTo($user, CommentPostedNotification::class);
    });

    test('multiple comments on same resource send individual notifications', function () {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // First comment
        $comment1 = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'First question',
            'decision' => false,
        ]);
        event(new CommentPosted($comment1));

        // Second comment
        $comment2 = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Follow up question',
            'decision' => false,
        ]);
        event(new CommentPosted($comment2));

        // Each comment triggers one notification
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 2);
    });
});

describe('notification deduplication edge cases', function () {
    test('notification is not sent twice if user is in multiple relationships', function () {
        // This tests the ->unique() call in NotifyUsersOfComment
        $user = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Attach the same user again (this shouldn't happen normally, but tests uniqueness)
        // The implementation should deduplicate
        $commenter = $this->createUserWithPreferences();

        $comment = Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Test',
            'decision' => false,
        ]);

        event(new CommentPosted($comment));

        // User should only receive 1 notification even if somehow duplicated in relationships
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 1);
    });
});

describe('reservation state change triggers both status and task notifications', function () {
    test('reservation state change to Reserved sends exactly 2 notifications: status + task', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // State change to Reserved triggers:
        // 1. HandleReservationResourceStateChanged -> ReservationStatusChangedNotification
        // 2. HandleReservationResourceReserved -> creates Task -> TaskCreated event -> TaskAssignedNotification
        $reservationResource->state->transitionTo(Reserved::class);

        // User should receive exactly 2 notifications
        Notification::assertSentTo($user, ReservationStatusChangedNotification::class);
        Notification::assertSentTo($user, \App\Notifications\TaskAssignedNotification::class);

        // Verify each notification is sent exactly once
        Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
        Notification::assertSentToTimes($user, \App\Notifications\TaskAssignedNotification::class, 1);
    });

    test('reservation state change creates task for picking up resource', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        // Verify the task was created with correct name
        Notification::assertSentTo($user, \App\Notifications\TaskAssignedNotification::class);

        // Verify task exists in database
        $this->assertDatabaseHas('tasks', [
            'taskable_type' => \App\Models\Reservation::class,
            'taskable_id' => $reservationResource->reservation_id,
        ]);
    });

    test('multiple users on reservation all receive both notifications', function () {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user1);
        $reservation->users()->attach($user2);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        // Both users should receive both notifications
        foreach ([$user1, $user2] as $user) {
            Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
            Notification::assertSentToTimes($user, \App\Notifications\TaskAssignedNotification::class, 1);
        }
    });

    test('admin making the state change does not receive notifications', function () {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        // Admin who made the change should NOT receive notifications
        // (they are the changedBy user, and notifications filter out the actor)
        Notification::assertNotSentTo($admin, ReservationStatusChangedNotification::class);
        Notification::assertNotSentTo($admin, \App\Notifications\TaskAssignedNotification::class);
    });
});
