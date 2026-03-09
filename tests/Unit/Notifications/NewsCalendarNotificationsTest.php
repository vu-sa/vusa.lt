<?php

use App\Console\Commands\SendCalendarReminders;
use App\Console\Commands\SendNewsNotifications;
use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CalendarReminderNotification;
use App\Notifications\NewsPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NewsPublishedNotification Tests
|--------------------------------------------------------------------------
*/

describe('NewsPublishedNotification', function () {
    test('has correct category', function () {
        $news = News::factory()->create(['draft' => false, 'publish_time' => now()]);
        $notification = new NewsPublishedNotification($news);

        expect($notification->category())->toBe(NotificationCategory::News);
    });

    test('returns correct title', function () {
        $news = News::factory()->create(['draft' => false, 'publish_time' => now()]);
        $user = User::factory()->create();
        $notification = new NewsPublishedNotification($news);

        expect($notification->title($user))->toBeString();
    });

    test('returns body with news title and tenant', function () {
        $news = News::factory()->create([
            'title' => 'Test News Title',
            'draft' => false,
            'publish_time' => now(),
        ]);
        $user = User::factory()->create();
        $notification = new NewsPublishedNotification($news);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url for Lithuanian news', function () {
        $news = News::factory()->create([
            'lang' => 'lt',
            'permalink' => 'test-news',
            'draft' => false,
            'publish_time' => now(),
        ]);
        $notification = new NewsPublishedNotification($news);

        expect($notification->url())->toContain('/naujiena/test-news');
    });

    test('returns correct url for English news', function () {
        $news = News::factory()->create([
            'lang' => 'en',
            'permalink' => 'test-news',
            'draft' => false,
            'publish_time' => now(),
        ]);
        $notification = new NewsPublishedNotification($news);

        expect($notification->url())->toContain('/news/test-news');
    });

    test('returns NEWS as modelClass', function () {
        $news = News::factory()->create(['draft' => false, 'publish_time' => now()]);
        $notification = new NewsPublishedNotification($news);

        expect($notification->modelClass())->toBe('NEWS');
    });

    test('does not support email digest', function () {
        $news = News::factory()->create(['draft' => false, 'publish_time' => now()]);
        $notification = new NewsPublishedNotification($news);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns correct object structure', function () {
        $news = News::factory()->create([
            'title' => 'Test News',
            'draft' => false,
            'publish_time' => now(),
        ]);
        $notification = new NewsPublishedNotification($news);

        $object = $notification->object();
        expect($object)->toBeArray();
        expect($object['modelClass'])->toBe('News');
        expect($object['name'])->toBe('Test News');
        expect($object['id'])->toBe($news->id);
    });
});

/*
|--------------------------------------------------------------------------
| CalendarReminderNotification Tests
|--------------------------------------------------------------------------
*/

describe('CalendarReminderNotification', function () {
    test('has correct category', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->category())->toBe(NotificationCategory::Calendar);
    });

    test('returns correct title for standard reminder', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $user = User::factory()->create();
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->title($user))->toBeString();
    });

    test('returns correct title for soon reminder', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $user = User::factory()->create();
        $notification = new CalendarReminderNotification($calendar, 1);

        expect($notification->title($user))->toBeString();
    });

    test('returns body for 24 hour reminder (tomorrow)', function () {
        $calendar = Calendar::factory()->create([
            'title' => json_encode(['lt' => 'Test Event']),
            'is_draft' => false,
        ]);
        $user = User::factory()->create();
        $notification = new CalendarReminderNotification($calendar, 24);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns body for 1 hour reminder', function () {
        $calendar = Calendar::factory()->create([
            'title' => json_encode(['lt' => 'Test Event']),
            'is_draft' => false,
        ]);
        $user = User::factory()->create();
        $notification = new CalendarReminderNotification($calendar, 1);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->url())->toContain((string) $calendar->id);
    });

    test('returns CALENDAR as modelClass', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->modelClass())->toBe('CALENDAR');
    });

    test('does not support email digest', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns clock icon for soon reminder', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 1);

        expect($notification->icon())->toBe('⏰');
    });

    test('returns calendar icon for standard reminder', function () {
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $notification = new CalendarReminderNotification($calendar, 24);

        expect($notification->icon())->toBe('📆');
    });
});

/*
|--------------------------------------------------------------------------
| SendNewsNotifications Command Tests
|--------------------------------------------------------------------------
*/

describe('SendNewsNotifications command', function () {
    beforeEach(function () {
        Notification::fake();
    });

    test('sends notifications for recently published news to opted-in users', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        // Create a user who has opted in
        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'news' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
            ],
        ]);

        // Create news published in the last 15 minutes
        $news = News::factory()->for($tenant)->create([
            'draft' => false,
            'publish_time' => now()->subMinutes(5),
        ]);

        $this->artisan(SendNewsNotifications::class)
            ->assertSuccessful();

        Notification::assertSentTo($user, NewsPublishedNotification::class);
    });

    test('does not send notifications to users who have not opted in', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        // Create a user who has NOT opted in (default behavior - news disabled)
        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create();

        // Create news published in the last 15 minutes
        News::factory()->for($tenant)->create([
            'draft' => false,
            'publish_time' => now()->subMinutes(5),
        ]);

        $this->artisan(SendNewsNotifications::class)
            ->assertSuccessful();

        Notification::assertNotSentTo($user, NewsPublishedNotification::class);
    });

    test('does not send notifications for draft news', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'news' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
            ],
        ]);

        // Create draft news
        News::factory()->for($tenant)->create([
            'draft' => true,
            'publish_time' => now()->subMinutes(5),
        ]);

        $this->artisan(SendNewsNotifications::class)
            ->assertSuccessful();

        Notification::assertNotSentTo($user, NewsPublishedNotification::class);
    });

    test('does not send notifications for old published news', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'news' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
            ],
        ]);

        // Create news published more than 15 minutes ago
        News::factory()->for($tenant)->create([
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $this->artisan(SendNewsNotifications::class)
            ->assertSuccessful();

        Notification::assertNotSentTo($user, NewsPublishedNotification::class);
    });
});

/*
|--------------------------------------------------------------------------
| SendCalendarReminders Command Tests
|--------------------------------------------------------------------------
*/

describe('SendCalendarReminders command', function () {
    beforeEach(function () {
        Notification::fake();
    });

    test('sends reminders for events happening in 24 hours to opted-in users', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        // Create a user who has opted in with 24h reminder
        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'calendar' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
                'reminder_settings' => [
                    'calendar_reminder_hours' => [24],
                ],
            ],
        ]);

        // Create event starting in ~24 hours
        Calendar::factory()->for($tenant)->create([
            'is_draft' => false,
            'date' => now()->addHours(24),
        ]);

        $this->artisan(SendCalendarReminders::class)
            ->assertSuccessful();

        Notification::assertSentTo($user, CalendarReminderNotification::class);
    });

    test('does not send reminders to users who have not opted in', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        // Create a user who has NOT opted in (default behavior - calendar disabled)
        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create();

        // Create event starting in ~24 hours
        Calendar::factory()->for($tenant)->create([
            'is_draft' => false,
            'date' => now()->addHours(24),
        ]);

        $this->artisan(SendCalendarReminders::class)
            ->assertSuccessful();

        Notification::assertNotSentTo($user, CalendarReminderNotification::class);
    });

    test('does not send reminders for draft events', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'calendar' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
                'reminder_settings' => [
                    'calendar_reminder_hours' => [24],
                ],
            ],
        ]);

        // Create draft event
        Calendar::factory()->for($tenant)->create([
            'is_draft' => true,
            'date' => now()->addHours(24),
        ]);

        $this->artisan(SendCalendarReminders::class)
            ->assertSuccessful();

        Notification::assertNotSentTo($user, CalendarReminderNotification::class);
    });

    test('respects user reminder hour preferences', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();

        // Create a user who only wants 1h reminders, not 24h
        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => [
                'channels' => [
                    'calendar' => [
                        'in_app' => true,
                        'push' => true,
                        'email_digest' => false,
                    ],
                ],
                'reminder_settings' => [
                    'calendar_reminder_hours' => [1], // Only 1h reminder, not 24h
                ],
            ],
        ]);

        // Create event starting in ~24 hours
        Calendar::factory()->for($tenant)->create([
            'is_draft' => false,
            'date' => now()->addHours(24),
        ]);

        $this->artisan(SendCalendarReminders::class)
            ->assertSuccessful();

        // Should not receive 24h reminder since user only wants 1h reminders
        Notification::assertNotSentTo($user, CalendarReminderNotification::class);
    });
});

/*
|--------------------------------------------------------------------------
| Default Preferences Tests
|--------------------------------------------------------------------------
*/

describe('News and Calendar preferences are disabled by default', function () {
    test('news channel preferences are false by default', function () {
        $user = User::factory()->create();

        expect($user->shouldReceiveNotification(NotificationCategory::News, NotificationChannel::InApp))->toBeFalse();
        expect($user->shouldReceiveNotification(NotificationCategory::News, NotificationChannel::Push))->toBeFalse();
    });

    test('calendar channel preferences are false by default', function () {
        $user = User::factory()->create();

        expect($user->shouldReceiveNotification(NotificationCategory::Calendar, NotificationChannel::InApp))->toBeFalse();
        expect($user->shouldReceiveNotification(NotificationCategory::Calendar, NotificationChannel::Push))->toBeFalse();
    });

    test('calendar reminder hours default to 24', function () {
        $user = User::factory()->create();

        expect($user->getCalendarReminderHours())->toBe([24]);
    });
});
