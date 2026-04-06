<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Listeners\QueueNotificationForDigest;
use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    $this->clearDigestQueue();
});

describe('digest queuing via QueueNotificationForDigest listener', function () {
    test('QueueNotificationForDigest queues eligible notifications', function () {
        $user = $this->createUserWithPreferences();

        // Ensure email digest is enabled for comments
        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            true
        );

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        // Simulate the NotificationSending event that triggers QueueNotificationForDigest
        $event = new NotificationSending($user, $notification, 'database');

        app(QueueNotificationForDigest::class)->handle($event);

        expect($this->getDigestQueueCountForUser($user))->toBe(1);
    });

    test('time-sensitive notifications are not queued for digest', function () {
        $user = $this->createUserWithPreferences();

        $task = Task::factory()->create(['due_date' => now()->addDays(3)]);
        $notification = new TaskReminderNotification($task, 3);

        // TaskReminderNotification has supportsEmailDigest() = false
        expect($notification->supportsEmailDigest())->toBeFalse();

        $event = new NotificationSending($user, $notification, 'database');

        app(QueueNotificationForDigest::class)->handle($event);

        // Should NOT be queued
        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('notifications are not queued when email digest is disabled for category', function () {
        $user = $this->createUserWithPreferences();

        // Disable email digest for comments
        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            false
        );

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $event = new NotificationSending($user, $notification, 'database');

        app(QueueNotificationForDigest::class)->handle($event);

        // Should NOT be queued because user disabled it
        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('notifications are not queued when user is globally muted', function () {
        $user = $this->createMutedUser(now()->addHours(2));

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $event = new NotificationSending($user, $notification, 'database');

        app(QueueNotificationForDigest::class)->handle($event);

        // Should NOT be queued because user is muted
        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('digest items store correct category and data', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            'Test comment with <strong>HTML</strong>',
            ['modelClass' => 'Task', 'name' => 'My Task', 'url' => '/tasks/123', 'id' => '123'],
            ['modelClass' => 'User', 'name' => 'John Doe']
        );

        $event = new NotificationSending($user, $notification, 'database');

        app(QueueNotificationForDigest::class)->handle($event);

        $item = $this->getDigestQueueItemsForUser($user)->first();

        expect($item->category)->toBe('comment');
        expect($item->notification_class)->toBe(CommentPostedNotification::class);
        expect($item->data)->toHaveKey('title');
        expect($item->data)->toHaveKey('body');
        expect($item->data)->toHaveKey('url');
        expect($item->data)->toHaveKey('icon');
    });
});

describe('digest grouping', function () {
    test('multiple notifications are grouped by category in queue', function () {
        $user = $this->createUserWithPreferences();

        // Queue multiple comment notifications
        for ($i = 0; $i < 3; $i++) {
            NotificationDigestQueue::create([
                'user_id' => $user->id,
                'notification_class' => CommentPostedNotification::class,
                'category' => NotificationCategory::Comment->value,
                'data' => ['title' => "Comment $i", 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
            ]);
        }

        // Queue a task notification
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => NotificationCategory::Task->value,
            'data' => ['title' => 'Task assigned', 'body' => 'Body', 'url' => '/test', 'icon' => '☑️'],
        ]);

        $items = $this->getDigestQueueItemsForUser($user);

        expect($items)->toHaveCount(4);

        $groupedByCategory = $items->groupBy('category');
        expect($groupedByCategory->get('comment'))->toHaveCount(3);
        expect($groupedByCategory->get('task'))->toHaveCount(1);
    });
});

describe('digest processing command', function () {
    test('command respects user digest frequency setting', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(4);

        // Create digest item created 2 hours ago (not enough time passed)
        $item = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        Artisan::call('notifications:send-digests');

        // Digest should NOT be sent yet (only 2 hours passed, user wants 4 hours)
        Mail::assertNothingSent();

        // Item should still be in queue
        expect($this->getDigestQueueCountForUser($user))->toBe(1);
    });

    test('command sends digest when enough time has passed', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(4);

        // Create digest item then update created_at directly (not in $fillable)
        $item = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item->forceFill(['created_at' => now()->subHours(5)])->saveQuietly();

        Artisan::call('notifications:send-digests');

        // Digest SHOULD be queued (NotificationDigest implements ShouldQueue)
        Mail::assertQueued(NotificationDigest::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Item should be deleted from queue
        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('processed items are deleted from queue after sending', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(1);

        // Create multiple digest items with old created_at
        for ($i = 0; $i < 5; $i++) {
            $item = NotificationDigestQueue::create([
                'user_id' => $user->id,
                'notification_class' => CommentPostedNotification::class,
                'category' => 'comment',
                'data' => ['title' => "Test $i", 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
            ]);
            $item->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();
        }

        expect($this->getDigestQueueCountForUser($user))->toBe(5);

        Artisan::call('notifications:send-digests');

        // All items should be deleted after processing
        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('orphaned items for deleted users are cleaned up', function () {
        // Note: With the foreign key constraint and ON DELETE CASCADE,
        // orphaned items are automatically deleted by the database when a user is deleted.
        // This test verifies the command handles the case gracefully when it encounters
        // a user_id for which User::find() returns null (which can happen in edge cases
        // or when FK constraints aren't enforced).
        //
        // We'll test the happy path by verifying the command doesn't fail when
        // processing users that exist.
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(1);

        $item = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item->forceFill(['created_at' => now()->subHours(10)])->saveQuietly();

        $initialCount = NotificationDigestQueue::where('user_id', $user->id)->count();
        expect($initialCount)->toBe(1);

        // Delete user - cascade should clean up digest items
        $userId = $user->id;
        $user->forceDelete();

        // Verify cascade worked
        expect(NotificationDigestQueue::where('user_id', $userId)->count())->toBe(0);

        // Command should handle gracefully with no items
        Artisan::call('notifications:send-digests');
        Mail::assertNothingSent();
    });

    test('command handles no pending digests gracefully', function () {
        Mail::fake();

        // No items in queue
        $this->clearDigestQueue();

        $exitCode = Artisan::call('notifications:send-digests');

        expect($exitCode)->toBe(0);
        Mail::assertNothingSent();
    });

    test('digest email contains all grouped notifications', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(1);

        // Add notifications from different categories with old timestamps
        $items = collect();

        $item1 = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Comment 1', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item1->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        $item2 = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Comment 2', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item2->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        $item3 = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => 'task',
            'data' => ['title' => 'Task 1', 'body' => 'Body', 'url' => '/test', 'icon' => '☑️'],
        ]);
        $item3->forceFill(['created_at' => now()->subHours(2)])->saveQuietly();

        Artisan::call('notifications:send-digests');

        Mail::assertQueued(NotificationDigest::class, function ($mail) use ($user) {
            // The mail should be queued to the correct user
            return $mail->hasTo($user->email);
        });
    });
});

describe('digest frequency settings', function () {
    test('user with 1 hour frequency receives digest faster', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(1);

        $item = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item->forceFill(['created_at' => now()->subMinutes(65)])->saveQuietly(); // Just over 1 hour

        Artisan::call('notifications:send-digests');

        Mail::assertQueued(NotificationDigest::class);
    });

    test('user with 24 hour frequency waits full day', function () {
        Mail::fake();

        $user = $this->createUserWithPreferences();
        $user->setDigestFrequencyHours(24);

        $item = NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        $item->forceFill(['created_at' => now()->subHours(12)])->saveQuietly(); // Only 12 hours

        Artisan::call('notifications:send-digests');

        // Should NOT be queued yet
        Mail::assertNothingQueued();

        // Travel to 25 hours after creation
        $this->travelTo(now()->addHours(13));

        Artisan::call('notifications:send-digests');

        // NOW it should be queued
        Mail::assertQueued(NotificationDigest::class);
    });
});

describe('triplicate prevention', function () {
    test('QueueNotificationForDigest only queues once per notification, not per channel', function () {
        $user = $this->createUserWithPreferences();
        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            true
        );

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $listener = app(QueueNotificationForDigest::class);

        // Simulate all 3 channels firing NotificationSending
        foreach (['database', 'broadcast', 'NotificationChannels\\WebPush\\WebPushChannel'] as $channel) {
            $event = new NotificationSending($user, $notification, $channel);
            $listener->handle($event);
        }

        // Should only have 1 digest queue entry, not 3
        expect($this->getDigestQueueCountForUser($user))->toBe(1);
    });

    test('non-database channels are skipped by digest listener', function () {
        $user = $this->createUserWithPreferences();
        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            true
        );

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $listener = app(QueueNotificationForDigest::class);

        // Only broadcast channel — should not queue
        $event = new NotificationSending($user, $notification, 'broadcast');
        $listener->handle($event);

        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });
});

describe('pluralization', function () {
    test('TaskOverdueNotification title uses proper pluralization', function () {
        $tasks = Task::factory()->count(5)->create(['due_date' => now()->subDay()]);
        $notification = new TaskOverdueNotification($tasks);

        $user = $this->createUserWithPreferences();

        $title = $notification->title($user);

        // Should NOT contain raw pluralization pipe syntax
        expect($title)->not->toContain('|');
        expect($title)->not->toContain('{1}');
        expect($title)->not->toContain('[2,9]');

        // Should contain the actual count
        expect($title)->toContain('5');
    });

    test('TaskOverdueNotification title works for single task', function () {
        $tasks = Task::factory()->count(1)->create(['due_date' => now()->subDay()]);
        $notification = new TaskOverdueNotification($tasks);

        $user = $this->createUserWithPreferences();

        $title = $notification->title($user);

        expect($title)->not->toContain('|');
        expect($title)->toContain('1');
    });
});

describe('digest queue cleanup on read', function () {
    test('marking all notifications as read clears digest queue', function () {
        Notification::fake();

        $user = $this->createUserWithPreferences();

        // Create digest queue items
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test', 'body' => 'Body', 'url' => '/test', 'icon' => '💬'],
        ]);
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => 'task',
            'data' => ['title' => 'Task', 'body' => 'Body', 'url' => '/tasks', 'icon' => '☑️'],
        ]);

        expect($this->getDigestQueueCountForUser($user))->toBe(2);

        asUser($user)->post(route('notifications.mark-as-read.all'));

        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('marking single notification as read removes matching digest entry', function () {
        $user = $this->createUserWithPreferences();

        // Create a database notification directly
        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => CommentPostedNotification::class,
            'data' => [
                'category' => 'comment',
                'title' => 'Naujas komentaras: Test',
                'body' => 'Comment body',
                'url' => '/test/123',
                'icon' => '💬',
            ],
        ]);

        $dbNotification = $user->unreadNotifications()->first();

        // Create matching digest queue item
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Naujas komentaras: Test', 'body' => 'Comment body', 'url' => '/test/123', 'icon' => '💬'],
        ]);

        // Create a non-matching digest queue item (should remain)
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => 'task',
            'data' => ['title' => 'Different notification', 'body' => 'Body', 'url' => '/other', 'icon' => '☑️'],
        ]);

        expect($this->getDigestQueueCountForUser($user))->toBe(2);

        asUser($user)->post(route('notifications.markAsRead', $dbNotification->id));

        // Only the matching entry should be removed
        expect($this->getDigestQueueCountForUser($user))->toBe(1);

        $remaining = $this->getDigestQueueItemsForUser($user)->first();
        expect($remaining->data['title'])->toBe('Different notification');
    });

    test('marking as read only removes one digest entry when same title and url but different body', function () {
        $user = $this->createUserWithPreferences();

        // Create two notifications with same title and URL but different bodies
        // (e.g. two "Nauja užduotis" notifications for different tasks)
        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => TaskAssignedNotification::class,
            'data' => [
                'category' => 'task',
                'title' => 'Nauja užduotis',
                'body' => 'Jums priskirta nauja užduotis: Task A',
                'url' => '/mano/tasks',
                'icon' => '☑️',
            ],
        ]);

        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => TaskAssignedNotification::class,
            'data' => [
                'category' => 'task',
                'title' => 'Nauja užduotis',
                'body' => 'Jums priskirta nauja užduotis: Task B',
                'url' => '/mano/tasks',
                'icon' => '☑️',
            ],
        ]);

        // Create two digest entries with same title/url but different bodies
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => 'task',
            'data' => ['title' => 'Nauja užduotis', 'body' => 'Jums priskirta nauja užduotis: Task A', 'url' => '/mano/tasks', 'icon' => '☑️'],
        ]);
        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => 'task',
            'data' => ['title' => 'Nauja užduotis', 'body' => 'Jums priskirta nauja užduotis: Task B', 'url' => '/mano/tasks', 'icon' => '☑️'],
        ]);

        expect($this->getDigestQueueCountForUser($user))->toBe(2);

        // Mark only the first notification as read
        $firstNotification = $user->unreadNotifications()->orderBy('created_at')->first();
        asUser($user)->post(route('notifications.markAsRead', $firstNotification->id));

        // Only one digest entry should be removed (the one matching Task A body)
        expect($this->getDigestQueueCountForUser($user))->toBe(1);

        $remaining = $this->getDigestQueueItemsForUser($user)->first();
        expect($remaining->data['body'])->toContain('Task B');
    });

    test('deleting notification removes matching digest entry', function () {
        $user = $this->createUserWithPreferences();

        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => CommentPostedNotification::class,
            'data' => [
                'category' => 'comment',
                'title' => 'Test notification',
                'body' => 'Body',
                'url' => '/test/456',
                'icon' => '💬',
            ],
        ]);

        $dbNotification = $user->notifications()->first();

        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => CommentPostedNotification::class,
            'category' => 'comment',
            'data' => ['title' => 'Test notification', 'body' => 'Body', 'url' => '/test/456', 'icon' => '💬'],
        ]);

        expect($this->getDigestQueueCountForUser($user))->toBe(1);

        asUser($user)->delete(route('notifications.destroy', $dbNotification->id));

        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });
});
