<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    $this->clearDigestQueue();
    $this->tenant = Tenant::query()->first();
});

function queueDigestItem(User $user, string $title = 'Test'): NotificationDigestQueue
{
    return NotificationDigestQueue::create([
        'user_id' => $user->id,
        'notification_class' => TaskAssignedNotification::class,
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => $title, 'body' => 'Body', 'url' => '/test', 'icon' => '📌'],
    ]);
}

describe('mail queue page', function (): void {
    test('lists pending digests grouped by recipient', function (): void {
        $admin = makeAdminUser($this->tenant);
        $recipient = makeUser($this->tenant);

        queueDigestItem($recipient, 'First');
        queueDigestItem($recipient, 'Second');

        asUser($admin)->get(route('mailQueue'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MailQueue')
                ->where('totals.items', 2)
                ->where('totals.recipients', 1)
                ->has('recipients', 1)
                ->where('recipients.0.items_count', 2)
                ->where('recipients.0.user.id', $recipient->id)
            );
    });

    test('a user without the system permission cannot open it', function (): void {
        asUser(makeUser($this->tenant))->get(route('mailQueue'))->assertStatus(403);
    });
});

describe('discarding queued email', function (): void {
    test('a super admin can drop one line', function (): void {
        $admin = makeAdminUser($this->tenant);
        $item = queueDigestItem(makeUser($this->tenant));
        $kept = queueDigestItem(makeUser($this->tenant));

        asUser($admin)->delete(route('mailQueue.destroy', $item->id))->assertRedirect();

        expect(NotificationDigestQueue::find($item->id))->toBeNull()
            ->and(NotificationDigestQueue::find($kept->id))->not->toBeNull();
    });

    test('a super admin can drop a recipient entire pending digest', function (): void {
        $admin = makeAdminUser($this->tenant);
        $recipient = makeUser($this->tenant);
        $other = makeUser($this->tenant);

        queueDigestItem($recipient);
        queueDigestItem($recipient);
        $kept = queueDigestItem($other);

        asUser($admin)->delete(route('mailQueue.destroyForUser', $recipient->id))->assertRedirect();

        expect(NotificationDigestQueue::query()->where('user_id', $recipient->id)->count())->toBe(0)
            ->and(NotificationDigestQueue::find($kept->id))->not->toBeNull();
    });

    test('a super admin can empty the queue', function (): void {
        $admin = makeAdminUser($this->tenant);
        queueDigestItem(makeUser($this->tenant));
        queueDigestItem(makeUser($this->tenant));

        asUser($admin)->delete(route('mailQueue.destroyAll'))->assertRedirect();

        expect(NotificationDigestQueue::query()->count())->toBe(0);
    });

    test('a non-super-admin cannot discard queued email', function (): void {
        $item = queueDigestItem(makeUser($this->tenant));

        asUser(makeUser($this->tenant))->delete(route('mailQueue.destroy', $item->id))->assertStatus(403);

        expect(NotificationDigestQueue::find($item->id))->not->toBeNull();
    });
});
