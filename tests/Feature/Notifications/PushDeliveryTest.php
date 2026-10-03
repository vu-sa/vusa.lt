<?php

use App\Listeners\PruneRejectedPushSubscription;
use App\Models\Tenant;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\WebPushMessage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeUser(Tenant::query()->first());
    $this->subscription = $this->user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc', 'key', 'token', 'aes128gcm');
});

function pushFailure($subscription, int $status, string $body): NotificationFailed
{
    $report = new MessageSentReport(new Request('POST', $subscription->endpoint), new Response($status, [], $body), false, 'failed');

    return new NotificationFailed($report, $subscription, new WebPushMessage);
}

test('the device list returns the user\'s subscriptions under data', function (): void {
    asUser($this->user)
        ->getJson(route('push-subscription.index'))
        ->assertOk()
        ->assertJsonPath('data.0.endpoint', 'https://fcm.googleapis.com/fcm/send/abc');
});

test('a subscription made under another VAPID key is removed', function (int $status, string $body): void {
    app(PruneRejectedPushSubscription::class)->handle(pushFailure($this->subscription, $status, $body));

    expect($this->user->pushSubscriptions()->count())->toBe(0);
})->with([
    'FCM key hash mismatch' => [400, '{"reason":"VapidPkHashMismatch"}'],
    'Mozilla key mismatch' => [401, '{"message":"VAPID public key mismatch"}'],
]);

test('a transient failure keeps the subscription', function (): void {
    app(PruneRejectedPushSubscription::class)->handle(pushFailure($this->subscription, 500, 'oops'));

    expect($this->user->pushSubscriptions()->count())->toBe(1);
});
