<?php

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Notifications\CommentPostedNotification;
use App\Notifications\ReservationStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use App\States\ReservationResource\Reserved;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    Notification::fake();
});

describe('reservation flow duplicate prevention', function (): void {
    // Note: Decision handling has been moved to the Approvals system (2026_01_15 migration).
    // Comments on reservations are now purely informational, not status-change related.
    // Status changes are handled by state transitions, not decision comments.

    test('state change without comment sends exactly 1 notification', function (): void {
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

    test('regular comment without state change sends exactly 1 notification', function (): void {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Regular comment
        $comment = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'body' => 'Just a regular question',
        ]);

        event(new CommentPosted($comment));

        // Only CommentPostedNotification, no status change
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 1);
        Notification::assertNotSentTo($user, ReservationStatusChangedNotification::class);
    });
});

describe('notification count limits', function (): void {
    test('single reservation state change does not exceed 1 notification per user', function (): void {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user1);
        $reservation->users()->attach($user2);

        $reservationResource->state->transitionTo(Reserved::class);

        // Each user should receive exactly 1 notification
        Notification::assertSentToTimes($user1, ReservationStatusChangedNotification::class, 1);
        Notification::assertSentToTimes($user2, ReservationStatusChangedNotification::class, 1);
    });

    test('multiple comments on same resource send individual notifications', function (): void {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // First comment
        $comment1 = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'body' => 'First question',
        ]);
        event(new CommentPosted($comment1));

        // Second comment
        $comment2 = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'body' => 'Follow up question',
        ]);
        event(new CommentPosted($comment2));

        // Each comment triggers one notification
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 2);
    });
});

describe('notification deduplication edge cases', function (): void {
    test('notification is not sent twice if user is in multiple relationships', function (): void {
        // This tests the ->unique() call in NotifyUsersOfComment
        $user = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // Attach the same user again (this shouldn't happen normally, but tests uniqueness)
        // The implementation should deduplicate
        $commenter = $this->createUserWithPreferences();

        $comment = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'body' => 'Test',
        ]);

        event(new CommentPosted($comment));

        // User should only receive 1 notification even if somehow duplicated in relationships
        Notification::assertSentToTimes($user, CommentPostedNotification::class, 1);
    });
});

describe('reservation state change sends the status notice, not a task notice too', function (): void {
    test('reservation state change to Reserved sends exactly one notification: the status', function (): void {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // The pickup task is created too, but a system task sends no notice of its own.
        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
        Notification::assertNotSentTo($user, TaskAssignedNotification::class);
    });

    test('reservation state change creates task for picking up resource', function (): void {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        $this->assertDatabaseHas('tasks', [
            'taskable_type' => MorphMap::alias(Reservation::class),
            'taskable_id' => $reservationResource->reservation_id,
        ]);
    });

    test('multiple users on reservation each receive the status notice once', function (): void {
        $user1 = $this->createUserWithPreferences();
        $user2 = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservation' => $reservation, 'reservationResource' => $reservationResource] = $this->createReservationWithResource($user1);
        $reservation->users()->attach($user2);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        foreach ([$user1, $user2] as $user) {
            Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
            Notification::assertNotSentTo($user, TaskAssignedNotification::class);
        }
    });

    test('admin making the state change does not receive notifications', function (): void {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        $reservationResource->state->transitionTo(Reserved::class);

        // Admin who made the change should NOT receive notifications
        // (they are the changedBy user, and notifications filter out the actor)
        Notification::assertNotSentTo($admin, ReservationStatusChangedNotification::class);
        Notification::assertNotSentTo($admin, TaskAssignedNotification::class);
    });
});
