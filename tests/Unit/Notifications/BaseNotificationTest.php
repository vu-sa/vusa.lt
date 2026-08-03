<?php

use App\Enums\NotificationCategory;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

describe('via method', function (): void {
    test('via returns empty array when user is globally muted', function (): void {
        $user = $this->createMutedUser();

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $channels = $notification->via($user);

        expect($channels)->toBeEmpty();
    });

    test('via includes database, broadcast, and webpush by default', function (): void {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $channels = $notification->via($user);

        expect($channels)->toContain('database')
            ->toContain('broadcast')
            ->and($channels)->toContain(WebPushChannel::class);
    });
});

describe('toArray method', function (): void {
    test('toArray includes all standardized fields', function (): void {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p>Test <strong>comment</strong></p>',
            ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'John Doe', 'image' => '/photo.jpg']
        );

        $data = $notification->toArray($user);

        expect($data)->toHaveKeys(['category', 'modelClass', 'title', 'body', 'url', 'icon', 'color', 'actions', 'subject', 'object'])
            ->toMatchArray(['category' => NotificationCategory::Comment->value, 'subject' => ['modelClass' => 'User', 'name' => 'John Doe', 'image' => '/photo.jpg'], 'object' => ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1']]);
    });

    test('toArray category is string value not enum', function (): void {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        $data = $notification->toArray($user);

        expect($data['category'])->toBeString()
            ->toBe('comment');
    });
});

describe('toDigestItem method', function (): void {
    test('toDigestItem returns simplified structure', function (): void {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p>This is a very long comment that should be truncated when converted to digest format for email delivery.</p>',
            ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'John Doe']
        );

        $digestItem = $notification->toDigestItem($user);

        expect($digestItem)->toHaveKeys(['category', 'title', 'body', 'url', 'icon'])->not->toHaveKey('actions')->not->toHaveKey('subject')->not->toHaveKey('object');
    });

    test('toDigestItem strips HTML from body', function (): void {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p><strong>Bold</strong> and <em>italic</em> text</p>',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        $digestItem = $notification->toDigestItem($user);

        expect($digestItem['body'])->not->toContain('<p>')->not->toContain('<strong>')->not->toContain('<em>');
    });
});

describe('supportsEmailDigest', function (): void {
    test('most notifications support email digest by default', function (): void {
        $notification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('TaskReminderNotification does not support email digest', function (): void {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(3),
        ]);

        $notification = new TaskReminderNotification($task, 3);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });
});

describe('icon method', function (): void {
    test('icon returns category-appropriate emoji', function (): void {
        $commentNotification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        expect($commentNotification->icon())->toBe('💬');
    });

    test('TaskReminderNotification shows warning icon when due soon', function (): void {
        $task = Task::factory()->create([
            'due_date' => now()->addDay(),
        ]);

        $notification = new TaskReminderNotification($task, 1);

        expect($notification->icon())->toBe('⚠️');
    });

    test('TaskReminderNotification shows clock icon when not urgent', function (): void {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);

        $notification = new TaskReminderNotification($task, 7);

        expect($notification->icon())->toBe('⏰');
    });
});

describe('category method', function (): void {
    test('each notification returns correct category enum', function (): void {
        $commentNotification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        expect($commentNotification->category())->toBe(NotificationCategory::Comment);

        $task = Task::factory()->create(['due_date' => now()->addDays(3)]);
        $taskNotification = new TaskReminderNotification($task, 3);

        expect($taskNotification->category())->toBe(NotificationCategory::Task);
    });
});
