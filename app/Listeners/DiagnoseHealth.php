<?php

namespace App\Listeners;

use App\Services\ServiceProbes;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Makes Laravel's `/up` return 500 when anything www.vusa.lt depends on is broken, so one
 * external monitor covers them all. Mail, Mailman and analytics are deliberately left out.
 */
class DiagnoseHealth
{
    public const string CACHE_KEY = 'health.up.failing';

    /** `/up` is public: caching keeps it from being used to hammer Typesense and the database. */
    private const int CACHE_SECONDS = 30;

    private const float DISK_FULL_PERCENT = 95.0;

    public function __construct(
        private readonly SystemMonitorService $monitor,
        private readonly ServiceProbes $probes,
    ) {}

    public function handle(DiagnosingHealth $event): void
    {
        $failing = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => $this->failingChecks());

        if ($failing !== []) {
            throw new RuntimeException('Health check failed: '.implode(', ', $failing));
        }
    }

    /**
     * @return list<string>
     */
    private function failingChecks(): array
    {
        $checks = [
            'database' => fn (): bool => $this->monitor->getDatabaseStatus()['status'] !== 'error',
            'redis' => fn (): bool => $this->monitor->getRedisStatus()['status'] !== 'error',
            'typesense' => fn (): bool => $this->probes->typesenseIsUp(),
            'reverb' => fn (): bool => $this->probes->reverbStatus()['status'] !== 'error',
            'scheduler' => fn (): bool => $this->monitor->getSchedulerStatus()['status'] === 'healthy',
            'queue' => fn (): bool => $this->monitor->getQueueStatus()['status'] === 'healthy',
            'disk' => fn (): bool => ($this->probes->diskUsedPercent() ?? 0.0) < self::DISK_FULL_PERCENT,
        ];

        return array_keys(array_filter($checks, fn (callable $passes): bool => ! $passes()));
    }
}
