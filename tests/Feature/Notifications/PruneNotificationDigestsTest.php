<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function () {
    $this->clearDigestQueue();
});

/**
 * Create a digest item for a user, backdated by the given number of days.
 */
function makeDigestItem(User $user, int $daysOld): NotificationDigestQueue
{
    $item = NotificationDigestQueue::create([
        'user_id' => $user->id,
        'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'Test', 'body' => 'Test', 'url' => '/test', 'icon' => '📌'],
    ]);

    // created_at is not fillable, so it has to be set after creation.
    $item->forceFill(['created_at' => now()->subDays($daysOld)])->saveQuietly();

    return $item->refresh();
}

describe('notifications:prune-digests', function () {
    test('prunes items older than the cutoff and keeps newer ones', function () {
        $user = $this->createUserWithDigestEnabled();

        $stale = makeDigestItem($user, 30);
        $fresh = makeDigestItem($user, 2);

        Artisan::call('notifications:prune-digests', ['--older-than' => 7, '--force' => true]);

        expect(NotificationDigestQueue::find($stale->id))->toBeNull()
            ->and(NotificationDigestQueue::find($fresh->id))->not->toBeNull();
    });

    test('dry run deletes nothing', function () {
        $user = $this->createUserWithDigestEnabled();

        makeDigestItem($user, 30);
        makeDigestItem($user, 45);

        Artisan::call('notifications:prune-digests', [
            '--older-than' => 7,
            '--dry-run' => true,
            '--force' => true,
        ]);

        expect($this->getDigestQueueCountForUser($user))->toBe(2);
    });

    test('the cutoff is configurable', function () {
        $user = $this->createUserWithDigestEnabled();

        makeDigestItem($user, 10);
        makeDigestItem($user, 2);

        // A 1-day cutoff should take both.
        Artisan::call('notifications:prune-digests', ['--older-than' => 1, '--force' => true]);

        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('rejects a cutoff below one day', function () {
        $exitCode = Artisan::call('notifications:prune-digests', ['--older-than' => 0, '--force' => true]);

        expect($exitCode)->toBe(1);
    });

    test('leaves an empty queue alone', function () {
        $exitCode = Artisan::call('notifications:prune-digests', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and(NotificationDigestQueue::count())->toBe(0);
    });
});
