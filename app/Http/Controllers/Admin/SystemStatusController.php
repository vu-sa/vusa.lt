<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class SystemStatusController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $status = [
            'redis' => $this->getRedisStatus(),
            'database' => $this->getDatabaseStatus(),
            'cache' => $this->getCacheStatus(),
            'integrations' => $this->getIntegrationsStatus(),
            'system' => $this->getSystemStatus(),
        ];

        return Inertia::render('Admin/SystemStatus', [
            'status' => $status,
            'lastUpdated' => now()->toISOString(),
        ]);
    }

    private function getRedisStatus(): array
    {
        try {
            $info = Redis::info();
            $connected = true;

            return [
                'status' => 'healthy',
                'connected' => $connected,
                'memory_used' => $this->formatBytes($info['used_memory'] ?? 0),
                'memory_rss' => $this->formatBytes($info['used_memory_rss'] ?? 0),
                'memory_peak' => $this->formatBytes($info['used_memory_peak'] ?? 0),
                'connected_clients' => $info['connected_clients'] ?? 0,
                'commands_processed' => number_format($info['total_commands_processed'] ?? 0),
                'keyspace_hits' => number_format($info['keyspace_hits'] ?? 0),
                'keyspace_misses' => number_format($info['keyspace_misses'] ?? 0),
                'hit_ratio' => $this->calculateHitRatio($info),
                'uptime' => $this->formatUptime($info['uptime_in_seconds'] ?? 0),
                'version' => $info['redis_version'] ?? 'Unknown',
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'connected' => false,
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function getDatabaseStatus(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $start) * 1000;

            $dbSize = DB::select('SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?', [config('database.connections.mysql.database')]);

            return [
                'status' => 'healthy',
                'connected' => true,
                'connection_time' => round($connectionTime, 2).'ms',
                'database_size' => ($dbSize[0]->size_mb ?? 0).' MB',
                'driver' => config('database.default'),
                'version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'connected' => false,
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function getCacheStatus(): array
    {
        try {
            $testKey = 'system_status_test_'.time();
            $testValue = 'test_value';

            // Test cache write/read
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $working = $retrieved === $testValue;

            return [
                'status' => $working ? 'healthy' : 'warning',
                'driver' => config('cache.default'),
                'working' => $working,
                'test_result' => $working ? 'passed' : 'failed',
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'working' => false,
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function getIntegrationsStatus(): array
    {
        return [
            'microsoft' => [
                'configured' => ! empty(config('services.microsoft.client_id')) &&
                              ! empty(config('services.microsoft.client_secret')),
                'client_id_set' => ! empty(config('services.microsoft.client_id')),
                'client_secret_set' => ! empty(config('services.microsoft.client_secret')),
                'redirect_uri' => config('services.microsoft.redirect'),
                'status' => (! empty(config('services.microsoft.client_id')) &&
                           ! empty(config('services.microsoft.client_secret'))) ? 'configured' : 'missing',
            ],
            'sharepoint' => [
                'configured' => ! empty(config('services.sharepoint.client_id')) &&
                              ! empty(config('services.sharepoint.client_secret')),
                'client_id_set' => ! empty(config('services.sharepoint.client_id')),
                'client_secret_set' => ! empty(config('services.sharepoint.client_secret')),
                'tenant_id' => config('services.sharepoint.tenant_id'),
                'status' => (! empty(config('services.sharepoint.client_id')) &&
                           ! empty(config('services.sharepoint.client_secret'))) ? 'configured' : 'missing',
            ],
            'mail' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'configured' => ! empty(config('mail.mailers.smtp.host')),
                'status' => ! empty(config('mail.mailers.smtp.host')) ? 'configured' : 'missing',
            ],
            'scout' => [
                'driver' => config('scout.driver'),
                'configured' => ! empty(config('scout.driver')) && config('scout.driver') !== 'null',
                'status' => (! empty(config('scout.driver')) && config('scout.driver') !== 'null') ? 'configured' : 'disabled',
            ],
        ];
    }

    private function getSystemStatus(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'url' => config('app.url'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_space' => $this->getDiskSpace(),
        ];
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

    private function formatUptime($seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }

    private function getDiskSpace(): array
    {
        $path = base_path();

        try {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            $percentage = round(($used / $total) * 100, 1);

            return [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage' => $percentage.'%',
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to determine disk space',
            ];
        }
    }
}
