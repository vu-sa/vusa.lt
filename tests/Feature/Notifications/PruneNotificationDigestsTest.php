<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

beforeEach(function (): void {
    $this->clearDigestQueue();
});

/**
 * Create a digest item for a user, backdated by the given number of days.
 */
function makeDigestItem(User $user, int $daysOld): NotificationDigestQueue
{
    $item = NotificationDigestQueue::create([
        'user_id' => $user->id,
        'notification_class' => TaskAssignedNotification::class,
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'Test', 'body' => 'Test', 'url' => '/test', 'icon' => '📌'],
    ]);

    // created_at is not fillable, so it has to be set after creation.
    $item->forceFill(['created_at' => now()->subDays($daysOld)])->saveQuietly();

    return $item->refresh();
}

describe('notifications:prune-digests', function (): void {
    test('--all empties the queue regardless of age', function (): void {
        $user = $this->createUserWithDigestEnabled();

        $stale = makeDigestItem($user, 30);
        $fresh = makeDigestItem($user, 0);

        Artisan::call('notifications:prune-digests', ['--all' => true, '--force' => true]);

        expect(NotificationDigestQueue::query()->count())->toBe(0)
            ->and(NotificationDigestQueue::find($stale->id))->toBeNull()
            ->and(NotificationDigestQueue::find($fresh->id))->toBeNull();
    });

    test('prunes items older than the cutoff and keeps newer ones', function (): void {
        $user = $this->createUserWithDigestEnabled();

        $stale = makeDigestItem($user, 30);
        $fresh = makeDigestItem($user, 2);

        Artisan::call('notifications:prune-digests', ['--older-than' => 7, '--force' => true]);

        expect(NotificationDigestQueue::find($stale->id))->toBeNull()
            ->and(NotificationDigestQueue::find($fresh->id))->not->toBeNull();
    });

    test('dry run deletes nothing', function (): void {
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

    test('the cutoff is configurable', function (): void {
        $user = $this->createUserWithDigestEnabled();

        makeDigestItem($user, 10);
        makeDigestItem($user, 2);

        // A 1-day cutoff should take both.
        Artisan::call('notifications:prune-digests', ['--older-than' => 1, '--force' => true]);

        expect($this->getDigestQueueCountForUser($user))->toBe(0);
    });

    test('rejects a cutoff below one day', function (): void {
        $exitCode = Artisan::call('notifications:prune-digests', ['--older-than' => 0, '--force' => true]);

        expect($exitCode)->toBe(1);
    });

    test('leaves an empty queue alone', function (): void {
        $exitCode = Artisan::call('notifications:prune-digests', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and(NotificationDigestQueue::count())->toBe(0);
    });
});
