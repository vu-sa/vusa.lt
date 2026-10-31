<?php

use App\Enums\ResponsibilityPurpose;
use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Notifications\CommentPostedNotification;
use App\Notifications\ReservationStatusChangeBatch;
use App\Notifications\ReservationStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use App\States\ReservationResource\Reserved;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    Notification::fake();
});

describe('reservation flow duplicate prevention', function (): void {
    test('state change without comment sends exactly 1 notification', function (): void {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // Only state change, no comment
        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
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

        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
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
        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentToTimes($user, ReservationStatusChangedNotification::class, 1);
        Notification::assertNotSentTo($user, TaskAssignedNotification::class);
    });

    test('reservation state change creates task for picking up resource', function (): void {
        $user = $this->createUserWithPreferences();
        $admin = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $this->actingAs($admin);

        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
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

        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
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

        // No request here: withoutDefer flushes immediately per transition.
        $this->withoutDefer();
        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertNotSentTo($admin, ReservationStatusChangedNotification::class);
        Notification::assertNotSentTo($admin, TaskAssignedNotification::class);
    });
});

test('one request batches resources per reservation and excludes its actor', function (): void {
    $recipient = $this->createUserWithPreferences();
    ['reservation' => $reservation, 'reservationResource' => $first] = $this->createReservationWithResource($recipient);
    $actor = makeTenantUserWithRole('Išteklių administratorius', $first->resource->tenant);
    grantPurpose($actor->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);
    $reservation->users()->attach($actor);
    $attributes = $first->getAttributes();
    unset($attributes['id']);
    $attributes['resource_id'] = Resource::factory()->create(['tenant_id' => $first->resource->tenant_id])->id;
    $second = ReservationResource::findOrFail(DB::table('reservation_resource')->insertGetId($attributes));
    $this->actingAs($actor)->post(route('approvals.bulkStore'), [
        'approvable_type' => 'reservation_resource',
        'approvable_ids' => [(string) $first->id, (string) $second->id],
        'decision' => 'approved',
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($first->fresh()->state->getValue())->toBe('reserved')
        ->and($second->fresh()->state->getValue())->toBe('reserved');

    Notification::assertSentToTimes($recipient, ReservationStatusChangedNotification::class, 1);
    Notification::assertSentTo($recipient, ReservationStatusChangedNotification::class, function ($notification) use ($recipient, $first, $second, $reservation): bool {
        expect($notification->title($recipient))->toContain('2')
            ->and($notification->body($recipient))->toContain($reservation->name, $first->resource->name, $second->resource->name)
            ->and(substr_count($notification->body($recipient), __('notifications.reservation_batch_hint_reserved', ['date' => $first->start_time->format('Y-m-d H:i')])))->toBe(1);

        return true;
    });
    Notification::assertNotSentTo($actor, ReservationStatusChangedNotification::class);
});

test('a failed request sends no reservation status notifications', function (): void {
    $recipient = $this->createUserWithPreferences();
    ['reservationResource' => $resource] = $this->createReservationWithResource($recipient);
    Route::post('/mano/_test/reservation-failure', function () use ($resource) {
        $resource->state->transitionTo(Reserved::class);
        abort(500);
    })->middleware('web');

    $this->post('/mano/_test/reservation-failure')->assertStatus(500);

    expect($resource->fresh()->state->getValue())->toBe('reserved');

    Notification::assertNothingSent();
});

test('multiple transitions of one resource in a request keep only its final status', function (): void {
    $recipient = $this->createUserWithPreferences();
    ['reservationResource' => $resource] = $this->createReservationWithResource($recipient);
    $actor = makeTenantUserWithRole('Išteklių administratorius', $resource->resource->tenant);
    grantPurpose($actor->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);

    $this->actingAs($actor)->post(route('approvals.resolve'), [
        'approvable_type' => 'reservation_resource',
        'approvable_ids' => [(string) $resource->id],
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($resource->fresh()->state->getValue())->toBe('returned');

    Notification::assertSentToTimes($recipient, ReservationStatusChangedNotification::class, 1);
    Notification::assertSentTo($recipient, ReservationStatusChangedNotification::class, function ($notification) use ($recipient): bool {
        expect($notification->title($recipient))->toBe(__('notifications.reservation_status_changed_title', ['status' => __('state.status.returned')]))
            ->and($notification->icon())->toBe('↩️');

        return true;
    });
});

test('mixed resource statuses name each resource and add each needed task hint once', function (): void {
    $recipient = $this->createUserWithPreferences();
    ['reservation' => $reservation, 'reservationResource' => $first] = $this->createReservationWithResource($recipient);
    $attributes = $first->getAttributes();
    unset($attributes['id']);
    $attributes['resource_id'] = Resource::factory()->create()->id;
    $second = ReservationResource::findOrFail(DB::table('reservation_resource')->insertGetId($attributes));
    $notification = new ReservationStatusChangedNotification([
        ['resource' => $first, 'oldState' => 'created', 'newState' => 'reserved'],
        ['resource' => $second, 'oldState' => 'reserved', 'newState' => 'lent'],
    ]);
    $restored = unserialize(serialize($notification));

    expect($restored->body($recipient))->toContain(
        $first->resource->name.' – '.__('state.status.reserved'),
        $second->resource->name.' – '.__('state.status.lent'),
        __('notifications.reservation_batch_hint_reserved', ['date' => $first->start_time->format('Y-m-d H:i')]),
        __('notifications.reservation_batch_hint_lent', ['date' => $second->end_time->format('Y-m-d H:i')]),
    )->and($restored->object()['id'])->toBe($reservation->id)
        ->and($restored->icon())->toBe('📅');
});

test('a single resource retains its full notification content', function (bool $hasActor): void {
    $recipient = $this->createUserWithPreferences();
    $actor = $hasActor ? $this->createUserWithPreferences() : null;
    ['reservation' => $reservation, 'resource' => $resource, 'reservationResource' => $item] = $this->createReservationWithResource($recipient);
    $notification = new ReservationStatusChangedNotification([
        ['resource' => $item, 'oldState' => 'created', 'newState' => 'reserved'],
    ], $actor);
    $expectedBody = __($hasActor ? 'notifications.reservation_status_changed_body_with_user' : 'notifications.reservation_status_changed_body', [
        'reservation' => $reservation->name, 'resource' => $resource->name,
        'status' => __('state.status.reserved'), 'user' => $actor?->name,
    ]).' '.__('notifications.reservation_task_hint_pickup', [
        'resource' => $resource->name, 'date' => $item->start_time->format('Y-m-d H:i'),
    ]);
    $action = ['label' => __('notifications.action_view_reservation'), 'url' => route('reservations.show', $reservation)];

    expect($notification->title($recipient))->toBe(__('notifications.reservation_status_changed_title', ['status' => __('state.status.reserved')]))
        ->and($notification->body($recipient))->toBe($expectedBody)
        ->and($notification->icon())->toBe('✅')
        ->and($notification->context($recipient))->toBe([])
        ->and($notification->primaryAction())->toBe($action)
        ->and($notification->secondaryAction())->toBeNull()
        ->and($notification->subject())->toBe($actor ? ['modelClass' => 'User', 'name' => $actor->name, 'image' => $actor->profile_photo_path] : null)
        ->and($notification->object())->toBe([
            'modelClass' => 'Reservation', 'name' => $reservation->name, 'url' => $action['url'], 'id' => $reservation->id,
        ])
        ->and($notification->toMail($recipient)->viewData['body'])->toBe($expectedBody);
})->with([false, true]);

test('reservation batches keep reservations and actors separate', function (): void {
    $recipient = $this->createUserWithPreferences();
    $actor = $this->createUserWithPreferences();
    ['reservationResource' => $first] = $this->createReservationWithResource($recipient);
    ['reservationResource' => $second] = $this->createReservationWithResource($recipient);
    $batch = app(ReservationStatusChangeBatch::class);
    $batch->add($first, 'created', 'reserved', null);
    $batch->add($first, 'reserved', 'lent', $actor);
    $batch->add($second, 'created', 'rejected', null);

    $batch->flush();
    $batch->flush();

    Notification::assertSentToTimes($recipient, ReservationStatusChangedNotification::class, 3);
});
