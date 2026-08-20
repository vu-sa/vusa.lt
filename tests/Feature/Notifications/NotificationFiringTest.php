<?php

use App\Events\CommentPosted;
use App\Events\TaskCreated;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\ReservationResource;
use App\Models\Task;
use App\Models\Tenant;
use App\Notifications\CommentPostedNotification;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\MeetingReminderNotification;
use App\Notifications\ReservationStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use App\States\ReservationResource\Reserved;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    Notification::fake();
    // Ensure queue runs synchronously for listeners
    config(['queue.default' => 'sync']);
});

describe('task notifications', function (): void {
    test('TaskAssignedNotification fires on task creation event', function (): void {
        $user = $this->createUserWithPreferences();

        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentTo($user, TaskAssignedNotification::class);
    });

    test('TaskAssignedNotification is sent to all assigned users', function (): void {
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

    test('only one TaskAssignedNotification per user per task creation', function (): void {
        $user = $this->createUserWithPreferences();

        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentToTimes($user, TaskAssignedNotification::class, 1);
    });

    test('no TaskAssignedNotification for tasks without users', function (): void {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);
        // Don't attach any users

        event(new TaskCreated($task));

        Notification::assertNothingSent();
    });

    test('periodicity tasks send one specialized activity notification', function (): void {
        $user = $this->createUserWithPreferences();
        $institution = Institution::factory()->create();
        $task = Task::factory()->create([
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $institution->id,
            'action_type' => ActionType::PeriodicityGap,
            'metadata' => ['activity_status' => 'approaching'],
        ]);
        $task->users()->attach($user);

        event(new TaskCreated($task));

        Notification::assertSentToTimes($user, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($user, TaskAssignedNotification::class);
        Notification::assertSentTo(
            $user,
            InstitutionActivityNotification::class,
            function (InstitutionActivityNotification $notification) use ($user, $institution): bool {
                $data = $notification->toArray($user);

                return $data['title'] === __('visak.activity.activity_status.approaching')
                    && str_contains($data['body'], $institution->name)
                    && count($data['actions']) === 2;
            }
        );
    });
});

describe('meeting reminder notifications', function (): void {
    test('honors a configured reminder hour', function (): void {
        $this->travelTo('2025-11-15 10:00:00');

        $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();
        $duty = Duty::factory()->for($institution)->create();
        $user = $this->createUserWithPreferences();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
        $user->setMeetingReminderHours([6]);

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => now()->addHours(6)]);

        $this->artisan('notifications:meeting-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            MeetingReminderNotification::class,
            fn (MeetingReminderNotification $notification): bool => str_contains($notification->body($user), '6')
        );
    });
});

describe('comment notifications', function (): void {
    test('CommentPostedNotification fires on new comment event', function (): void {
        $user = $this->createUserWithPreferences();
        $commenter = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        $comment = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'body' => 'Test comment',
        ]);

        event(new CommentPosted($comment));

        Notification::assertSentTo($user, CommentPostedNotification::class);
    });

    // Note: Decision/status-change comments are now handled through the Approvals system
    // (see 2026_01_15_160000 migration). Comments are purely for discussion.
});

describe('reservation notifications', function (): void {
    test('ReservationStatusChangedNotification fires on state transition', function (): void {
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource, 'reservation' => $reservation] = $this->createReservationWithResource($user);

        // Manually dispatch state changed event (normally done by Spatie state machine)
        $initialState = $reservationResource->state;
        $reservationResource->state->transitionTo(Reserved::class);

        Notification::assertSentTo($user, ReservationStatusChangedNotification::class);
    });

    test('ReservationStatusChangedNotification contains correct state information', function (): void {
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

    test('ReservationStatusChangedNotification sends to all reservation users', function (): void {
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

describe('notification not sent to commenter', function (): void {
    test('comment author does not receive their own comment notification', function (): void {
        // The recipient resolver excludes the author from every group, so even
        // when the author is also part of the commentable's audience (here, the
        // reservation owner) they are never notified about their own comment.
        $user = $this->createUserWithPreferences();

        ['reservationResource' => $reservationResource] = $this->createReservationWithResource($user);

        // User comments on their own reservation
        $comment = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(ReservationResource::class),
            'commentable_id' => $reservationResource->id,
            'user_id' => $user->id, // Same user is the commenter
            'body' => 'My own comment',
        ]);

        event(new CommentPosted($comment));

        Notification::assertNotSentTo($user, CommentPostedNotification::class);
    });
});
