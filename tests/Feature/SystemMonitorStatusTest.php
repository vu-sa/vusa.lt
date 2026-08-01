<?php

use App\Console\Kernel;
use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::forget(Kernel::HEARTBEAT_CACHE_KEY);
    NotificationDigestQueue::query()->delete();

    $this->monitor = app(SystemMonitorService::class);
});

describe('scheduler status', function (): void {
    test('reports an error when the scheduler has never reported in', function (): void {
        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('error')
            ->and($status['running'])->toBeFalse()
            ->and($status['last_run'])->toBeNull();
    });

    test('reports healthy on a fresh heartbeat', function (): void {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['running'])->toBeTrue();
    });

    test('warns when the heartbeat is stale', function (): void {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->subMinutes(30)->toIso8601String());

        expect($this->monitor->getSchedulerStatus()['status'])->toBe('warning');
    });

    test('errors when the heartbeat is long gone', function (): void {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->subDays(3)->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('error')
            ->and($status['running'])->toBeFalse();
    });
});

describe('digest status', function (): void {
    test('an empty queue is healthy', function (): void {
        $status = $this->monitor->getDigestStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['pending_items'])->toBe(0);
    });

    test('a recent backlog is healthy', function (): void {
        makeQueueItem(hoursOld: 2);

        $status = $this->monitor->getDigestStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['pending_items'])->toBe(1)
            ->and($status['users_waiting'])->toBe(1);
    });

    test('a day-old backlog warns', function (): void {
        makeQueueItem(hoursOld: 30);

        expect($this->monitor->getDigestStatus()['status'])->toBe('warning');
    });

    test('a stale backlog errors', function (): void {
        makeQueueItem(hoursOld: 24 * 30);

        $status = $this->monitor->getDigestStatus();

        expect($status['status'])->toBe('error')
            ->and($status['oldest_age_hours'])->toBeGreaterThan(72);
    });
});

/**
 * Create a digest queue item backdated by the given number of hours.
 */
function makeQueueItem(int $hoursOld): NotificationDigestQueue
{
    $user = User::factory()->create();

    $item = NotificationDigestQueue::create([
        'user_id' => $user->id,
        'notification_class' => TaskAssignedNotification::class,
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'T', 'body' => 'B', 'url' => '/t', 'icon' => '📌'],
    ]);

    $item->forceFill(['created_at' => now()->subHours($hoursOld)])->saveQuietly();

    return $item;
}
