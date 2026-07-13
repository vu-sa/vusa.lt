<?php

use App\Enums\NotificationCategory;
use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    $this->clearDigestQueue();
    Mail::fake();
});

describe('profile.sendTestNotificationEmail', function () {
    test('sends a sample digest to the user digest addresses', function () {
        $user = $this->createUserWithDigestEnabled();

        asUser($user)
            ->postJson(route('profile.sendTestNotificationEmail'))
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertSent(NotificationDigest::class, function ($mail) use ($user) {
            return $mail->hasTo($user->getDigestEmails()[0]);
        });
    });

    test('the sample digest carries an item, so the template renders', function () {
        $user = $this->createUserWithDigestEnabled();

        asUser($user)->postJson(route('profile.sendTestNotificationEmail'))->assertOk();

        Mail::assertSent(NotificationDigest::class, function ($mail) {
            $items = collect($mail->groupedItems)->flatten(1);

            return $items->count() === 1 && ! empty($items->first()['title']);
        });
    });

    test('does not consume the pending digest items', function () {
        $user = $this->createUserWithDigestEnabled();

        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
            'category' => NotificationCategory::Task->value,
            'data' => ['title' => 'Real', 'body' => 'Real', 'url' => '/t', 'icon' => '📌'],
        ]);

        asUser($user)->postJson(route('profile.sendTestNotificationEmail'))->assertOk();

        // The test send is synthetic — the user's real backlog must survive it.
        expect($this->getDigestQueueCountForUser($user))->toBe(1);
    });

    test('guests cannot send test emails', function () {
        $this->postJson(route('profile.sendTestNotificationEmail'))
            ->assertUnauthorized();

        Mail::assertNothingSent();
    });
});
