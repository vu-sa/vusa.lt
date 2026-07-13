<?php

use App\Console\Kernel;
use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget(Kernel::HEARTBEAT_CACHE_KEY);
    NotificationDigestQueue::query()->delete();

    $this->monitor = app(SystemMonitorService::class);
});

describe('scheduler status', function () {
    test('reports an error when the scheduler has never reported in', function () {
        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('error')
            ->and($status['running'])->toBeFalse()
            ->and($status['last_run'])->toBeNull();
    });

    test('reports healthy on a fresh heartbeat', function () {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['running'])->toBeTrue();
    });

    test('warns when the heartbeat is stale', function () {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->subMinutes(30)->toIso8601String());

        expect($this->monitor->getSchedulerStatus()['status'])->toBe('warning');
    });

    test('errors when the heartbeat is long gone', function () {
        Cache::forever(Kernel::HEARTBEAT_CACHE_KEY, now()->subDays(3)->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('error')
            ->and($status['running'])->toBeFalse();
    });
});

describe('digest status', function () {
    test('an empty queue is healthy', function () {
        $status = $this->monitor->getDigestStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['pending_items'])->toBe(0);
    });

    test('a recent backlog is healthy', function () {
        makeQueueItem(hoursOld: 2);

        $status = $this->monitor->getDigestStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['pending_items'])->toBe(1)
            ->and($status['users_waiting'])->toBe(1);
    });

    test('a day-old backlog warns', function () {
        makeQueueItem(hoursOld: 30);

        expect($this->monitor->getDigestStatus()['status'])->toBe('warning');
    });

    test('a stale backlog errors', function () {
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
        'notification_class' => 'App\\Notifications\\TaskAssignedNotification',
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'T', 'body' => 'B', 'url' => '/t', 'icon' => '📌'],
    ]);

    $item->forceFill(['created_at' => now()->subHours($hoursOld)])->saveQuietly();

    return $item;
}
