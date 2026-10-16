<?php

use App\Enums\NotificationCategory;
use App\Jobs\QueueHeartbeat;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\ServiceProbes;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::forget(SystemMonitorService::HEARTBEAT_CACHE_KEY);
    Cache::forget(SystemMonitorService::QUEUE_HEARTBEAT_CACHE_KEY);
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
        Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, now()->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('healthy')
            ->and($status['running'])->toBeTrue();
    });

    test('warns when the heartbeat is stale', function (): void {
        Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, now()->subMinutes(30)->toIso8601String());

        expect($this->monitor->getSchedulerStatus()['status'])->toBe('warning');
    });

    test('errors when the heartbeat is long gone', function (): void {
        Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, now()->subDays(3)->toIso8601String());

        $status = $this->monitor->getSchedulerStatus();

        expect($status['status'])->toBe('error')
            ->and($status['running'])->toBeFalse();
    });
});

describe('queue status', function (): void {
    test('reports an error until a worker has processed the heartbeat job', function (): void {
        expect($this->monitor->getQueueStatus()['status'])->toBe('error');
    });

    test('is healthy once a worker has run the heartbeat job', function (): void {
        (new QueueHeartbeat)->handle();

        expect($this->monitor->getQueueStatus()['status'])->toBe('healthy');
    });

    test('warns when no job has been processed for over ten minutes', function (): void {
        Cache::forever(SystemMonitorService::QUEUE_HEARTBEAT_CACHE_KEY, now()->subMinutes(11)->toIso8601String());

        expect($this->monitor->getQueueStatus()['status'])->toBe('warning');
    });
});

describe('reverb status', function (): void {
    beforeEach(function (): void {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.options' => ['host' => '127.0.0.1', 'port' => 6001, 'scheme' => 'http'],
        ]);
        Http::preventStrayRequests();
    });

    test('is healthy when the Reverb server answers its up endpoint', function (): void {
        Http::fake(['http://127.0.0.1:6001/up' => Http::response('', 200)]);

        expect(app(ServiceProbes::class)->reverbStatus()['status'])->toBe('healthy');
    });

    test('errors when the Reverb server answers with a failure', function (): void {
        Http::fake(['http://127.0.0.1:6001/up' => Http::response('', 502)]);

        expect(app(ServiceProbes::class)->reverbStatus()['status'])->toBe('error');
    });

    test('errors when the Reverb server is unreachable', function (): void {
        Http::fake(['http://127.0.0.1:6001/up' => Http::failedConnection()]);

        expect(app(ServiceProbes::class)->reverbStatus()['status'])->toBe('error');
    });

    test('is not probed when broadcasting does not go through Reverb', function (): void {
        config(['broadcasting.default' => 'null']);
        Http::fake();

        expect(app(ServiceProbes::class)->reverbStatus()['status'])->toBe('disabled');
        Http::assertNothingSent();
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
