<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheStats extends Command
{
    protected $signature = 'cache:stats';

    protected $description = 'Display Redis cache statistics';

    public function handle()
    {
        try {
            $info = Redis::info();

            $this->info('Redis Cache Statistics:');
            $this->line('=======================');

            $this->table(['Metric', 'Value'], [
                ['Connected clients', $info['connected_clients'] ?? 'N/A'],
                ['Used memory', $this->formatBytes($info['used_memory'] ?? 0)],
                ['Used memory RSS', $this->formatBytes($info['used_memory_rss'] ?? 0)],
                ['Total commands processed', number_format($info['total_commands_processed'] ?? 0)],
                ['Total connections received', number_format($info['total_connections_received'] ?? 0)],
                ['Keyspace hits', number_format($info['keyspace_hits'] ?? 0)],
                ['Keyspace misses', number_format($info['keyspace_misses'] ?? 0)],
                ['Hit ratio', $this->calculateHitRatio($info)],
            ]);

            // Show keyspace info
            $this->line('');
            $this->info('Keyspace Information:');

            foreach ($info as $key => $value) {
                if (str_starts_with($key, 'db')) {
                    $this->line("{$key}: {$value}");
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed to connect to Redis: '.$e->getMessage());
        }
    }

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }

    private function calculateHitRatio(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 'N/A';
        }

        return round(($hits / $total) * 100, 2).'%';
    }
}
