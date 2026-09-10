<?php

use App\Models\Tenant;
use App\Notifications\Channels\IdempotentDatabaseChannel;
use App\Notifications\TestPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

test('the database channel binding resolves to the idempotent implementation', function () {
    expect(app(DatabaseChannel::class))->toBeInstanceOf(IdempotentDatabaseChannel::class);
});

test('re-sending a notification with the same id does not throw a duplicate-key error', function () {
    $user = makeUser(Tenant::query()->inRandomOrder()->first());

    $notification = new TestPushNotification;
    $notification->id = (string) Str::uuid();

    $channel = app(IdempotentDatabaseChannel::class);

    // Simulates a retried SendQueuedNotifications job re-running the database
    // channel after it already committed on a prior attempt.
    $channel->send($user, $notification);
    $channel->send($user, $notification);

    expect($user->notifications()->where('id', $notification->id)->count())->toBe(1);
});
