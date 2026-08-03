<?php

use App\Enums\NotificationCategory;
use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    $this->clearDigestQueue();
    Mail::fake();
});

describe('profile.sendTestNotificationEmail', function (): void {
    test('sends a sample digest to the user digest addresses', function (): void {
        $user = $this->createUserWithDigestEnabled();

        asUser($user)
            ->postJson(route('profile.sendTestNotificationEmail'))
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertSent(NotificationDigest::class, fn ($mail) => $mail->hasTo($user->getDigestEmails()[0]));
    });

    test('the sample digest carries an item, so the template renders', function (): void {
        $user = $this->createUserWithDigestEnabled();

        asUser($user)->postJson(route('profile.sendTestNotificationEmail'))->assertOk();

        Mail::assertSent(NotificationDigest::class, function ($mail) {
            $items = collect($mail->groupedItems)->flatten(1);

            return $items->count() === 1 && ! empty($items->first()['title']);
        });
    });

    test('does not consume the pending digest items', function (): void {
        $user = $this->createUserWithDigestEnabled();

        NotificationDigestQueue::create([
            'user_id' => $user->id,
            'notification_class' => TaskAssignedNotification::class,
            'category' => NotificationCategory::Task->value,
            'data' => ['title' => 'Real', 'body' => 'Real', 'url' => '/t', 'icon' => '📌'],
        ]);

        asUser($user)->postJson(route('profile.sendTestNotificationEmail'))->assertOk();

        // The test send is synthetic — the user's real backlog must survive it.
        expect($this->getDigestQueueCountForUser($user))->toBe(1);
    });

    test('guests cannot send test emails', function (): void {
        $this->postJson(route('profile.sendTestNotificationEmail'))
            ->assertUnauthorized();

        Mail::assertNothingSent();
    });
});
