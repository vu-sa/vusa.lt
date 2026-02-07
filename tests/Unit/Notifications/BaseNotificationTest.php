<?php

use App\Enums\NotificationCategory;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

describe('via method', function () {
    test('via returns empty array when user is globally muted', function () {
        $user = $this->createMutedUser();

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $channels = $notification->via($user);

        expect($channels)->toBeEmpty();
    });

    test('via includes database, broadcast, and webpush by default', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            'Test comment',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Commenter']
        );

        $channels = $notification->via($user);

        expect($channels)->toContain('database');
        expect($channels)->toContain('broadcast');
        expect(in_array(\NotificationChannels\WebPush\WebPushChannel::class, $channels))->toBeTrue();
    });
});

describe('toArray method', function () {
    test('toArray includes all standardized fields', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p>Test <strong>comment</strong></p>',
            ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'John Doe', 'image' => '/photo.jpg']
        );

        $data = $notification->toArray($user);

        expect($data)->toHaveKey('category');
        expect($data)->toHaveKey('modelClass');
        expect($data)->toHaveKey('title');
        expect($data)->toHaveKey('body');
        expect($data)->toHaveKey('url');
        expect($data)->toHaveKey('icon');
        expect($data)->toHaveKey('color');
        expect($data)->toHaveKey('actions');
        expect($data)->toHaveKey('subject');
        expect($data)->toHaveKey('object');

        expect($data['category'])->toBe(NotificationCategory::Comment->value);
        expect($data['subject'])->toBe(['modelClass' => 'User', 'name' => 'John Doe', 'image' => '/photo.jpg']);
        expect($data['object'])->toBe(['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1']);
    });

    test('toArray category is string value not enum', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        $data = $notification->toArray($user);

        expect($data['category'])->toBeString();
        expect($data['category'])->toBe('comment');
    });
});

describe('toDigestItem method', function () {
    test('toDigestItem returns simplified structure', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p>This is a very long comment that should be truncated when converted to digest format for email delivery.</p>',
            ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/tasks/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'John Doe']
        );

        $digestItem = $notification->toDigestItem($user);

        expect($digestItem)->toHaveKeys(['category', 'title', 'body', 'url', 'icon']);
        expect($digestItem)->not->toHaveKey('actions');
        expect($digestItem)->not->toHaveKey('subject');
        expect($digestItem)->not->toHaveKey('object');
    });

    test('toDigestItem strips HTML from body', function () {
        $user = $this->createUserWithPreferences();

        $notification = new CommentPostedNotification(
            '<p><strong>Bold</strong> and <em>italic</em> text</p>',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        $digestItem = $notification->toDigestItem($user);

        expect($digestItem['body'])->not->toContain('<p>');
        expect($digestItem['body'])->not->toContain('<strong>');
        expect($digestItem['body'])->not->toContain('<em>');
    });
});

describe('supportsEmailDigest', function () {
    test('most notifications support email digest by default', function () {
        $notification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('TaskReminderNotification does not support email digest', function () {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(3),
        ]);

        $notification = new TaskReminderNotification($task, 3);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });
});

describe('icon method', function () {
    test('icon returns category-appropriate emoji', function () {
        $commentNotification = new CommentPostedNotification(
            'Test',
            ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Test']
        );

        expect($commentNotification->icon())->toBe('💬');
    });

    test('TaskReminderNotification shows warning icon when due soon', function () {
        $task = Task::factory()->create([
            'due_date' => now()->addDay(),
        ]);

        $notification = new TaskReminderNotification($task, 1);

        expect($notification->icon())->toBe('⚠️');
    });

    test('TaskReminderNotification shows clock icon when not urgent', function () {
        $task = Task::factory()->create([
            'due_date' => now()->addDays(7),
        ]);

        $notification = new TaskReminderNotification($task, 7);

        expect($notification->icon())->toBe('⏰');
    });
});

describe('category method', function () {
    test('each notification returns correct category enum', function () {
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
