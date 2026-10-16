<?php

namespace App\Jobs;

use App\Services\SystemMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        Cache::forever(SystemMonitorService::QUEUE_HEARTBEAT_CACHE_KEY, now()->toIso8601String());
    }
}
