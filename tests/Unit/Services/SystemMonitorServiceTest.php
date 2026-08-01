<?php

use App\Services\SystemMonitorService;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

describe('SystemMonitorService', function (): void {
    beforeEach(function (): void {
        $this->service = new SystemMonitorService;
    });

    describe('getAllStatus', function (): void {
        test('returns all required status sections', function (): void {
            $status = $this->service->getAllStatus();

            expect($status)->toHaveKeys(['redis', 'database', 'cache', 'typesense', 'integrations', 'system']);
        });
    });

    describe('getRedisStatus', function (): void {
        test('returns healthy status when Redis responds', function (): void {
            Redis::shouldReceive('info')->once()->andReturn([
                'used_memory' => 1024 * 1024,
                'used_memory_rss' => 2 * 1024 * 1024,
                'used_memory_peak' => 3 * 1024 * 1024,
                'connected_clients' => 5,
                'total_commands_processed' => 1000,
                'keyspace_hits' => 800,
                'keyspace_misses' => 200,
                'uptime_in_seconds' => 3661,
                'redis_version' => '7.0.0',
            ]);

            $status = $this->service->getRedisStatus();

            expect($status)
                ->status->toBe('healthy')
                ->connected->toBeTrue()
                ->memory_used->toBe('1 MB')
                ->version->toBe('7.0.0')
                ->hit_ratio->toBe('80%')
                ->uptime->toBe('1h 1m')
                ->and($status)->toHaveKey('last_check');
        });

        test('returns error status when Redis throws', function (): void {
            Redis::shouldReceive('info')->once()->andThrow(new Exception('Connection refused'));

            $status = $this->service->getRedisStatus();

            expect($status)
                ->status->toBe('error')
                ->connected->toBeFalse()
                ->error->toBe('Connection refused');
        });
    });

    describe('getDatabaseStatus', function (): void {
        test('returns healthy status when database connects', function (): void {
            $pdo = Mockery::mock(PDO::class);
            $connection = Mockery::mock(Connection::class);
            $connection->shouldReceive('getPdo')->once()->andReturn($pdo);

            DB::shouldReceive('connection')->once()->andReturn($connection);
            DB::shouldReceive('select')->andReturnUsing(function ($query) {
                if (str_contains($query, 'information_schema')) {
                    return [(object) ['size_mb' => 150.5]];
                }

                return [(object) ['version' => '8.0.30']];
            });

            $status = $this->service->getDatabaseStatus();

            expect($status)
                ->status->toBe('healthy')
                ->connected->toBeTrue()
                ->database_size->toBe('150.5 MB')
                ->version->toBe('8.0.30')
                ->and($status)->toHaveKey('connection_time');
        });

        test('returns error status when database connection fails', function (): void {
            DB::shouldReceive('connection')->once()->andThrow(new Exception('SQLSTATE[HY000]'));

            $status = $this->service->getDatabaseStatus();

            expect($status)
                ->status->toBe('error')
                ->connected->toBeFalse()
                ->error->toContain('SQLSTATE');
        });
    });

    describe('getCacheStatus', function (): void {
        test('returns healthy status when cache read/write works', function (): void {
            Cache::shouldReceive('put')->once()->andReturnTrue();
            Cache::shouldReceive('get')->once()->andReturn('test_value');
            Cache::shouldReceive('forget')->once()->andReturnTrue();

            $status = $this->service->getCacheStatus();

            expect($status)
                ->status->toBe('healthy')
                ->working->toBeTrue()
                ->test_result->toBe('passed');
        });

        test('returns warning when cache read/write mismatches', function (): void {
            Cache::shouldReceive('put')->once()->andReturnTrue();
            Cache::shouldReceive('get')->once()->andReturn('wrong_value');
            Cache::shouldReceive('forget')->once()->andReturnTrue();

            $status = $this->service->getCacheStatus();

            expect($status)
                ->status->toBe('warning')
                ->working->toBeFalse()
                ->test_result->toBe('failed');
        });

        test('returns error status when cache throws', function (): void {
            Cache::shouldReceive('put')->once()->andThrow(new Exception('Cache store unavailable'));

            $status = $this->service->getCacheStatus();

            expect($status)
                ->status->toBe('error')
                ->working->toBeFalse()
                ->error->toBe('Cache store unavailable');
        });
    });

    describe('getIntegrationsStatus', function (): void {
        test('returns correct structure for all integrations', function (): void {
            config([
                'services.microsoft.client_id' => 'ms-client',
                'services.microsoft.client_secret' => 'ms-secret',
                'services.microsoft.redirect' => 'https://example.com/callback',
                'services.sharepoint.client_id' => null,
                'services.sharepoint.client_secret' => null,
                'services.sharepoint.tenant_id' => 'tenant-1',
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => 'smtp.example.com',
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.encryption' => 'tls',
                'scout.driver' => 'database',
                'scout.queue' => true,
                'scout.after_commit' => false,
                'scout.chunk.searchable' => 500,
            ]);

            $status = $this->service->getIntegrationsStatus();

            expect($status)->toHaveKeys(['microsoft', 'sharepoint', 'mail', 'scout']);

            // NOTE: each nested assertion below uses an explicit `toMatchArray()` call
            // rather than chaining `->key->toBe()` across separate `expect($status[...])`
            // statements. Pest Rector's `UseToMatchArrayRector` walks past intermediate
            // property/assertion nodes to find the outermost `expect()` root and only the
            // *last* assertion in the chain; when several consecutive `expect($status[...])`
            // statements share the same base `$status` variable it collapses them into one
            // bogus `expect($status)->toMatchArray([...])`, using only each chain's final
            // value and silently discarding every earlier assertion. Writing the intended
            // `toMatchArray()` ourselves keeps `--rector` a no-op here (the rule only
            // rewrites `toBe`/`toEqual` chains, so it leaves this form untouched).
            expect($status['microsoft'])->toMatchArray([
                'configured' => true,
                'client_id_set' => true,
                'client_secret_set' => true,
                'redirect_uri' => 'https://example.com/callback',
                'status' => 'configured',
            ]);

            expect($status['sharepoint'])->toMatchArray([
                'configured' => false,
                'status' => 'missing',
            ])
                ->and($status['mail'])->toMatchArray([
                    'configured' => true,
                    'host' => 'smtp.example.com',
                    'status' => 'configured',
                ])
                ->and($status['scout'])->toMatchArray([
                    'configured' => true,
                    'driver' => 'database',
                    'queue_enabled' => true,
                    'chunk_size' => 500,
                    'status' => 'configured',
                ]);
        });

        test('reflects missing configuration correctly', function (): void {
            config([
                'services.microsoft.client_id' => null,
                'services.microsoft.client_secret' => null,
                'services.microsoft.redirect' => null,
                'mail.default' => 'log',
                'mail.mailers.smtp.host' => null,
                'scout.driver' => 'null',
            ]);

            $status = $this->service->getIntegrationsStatus();

            expect($status['microsoft']['status'])->toBe('missing')
                ->and($status['mail']['status'])->toBe('missing')
                ->and($status['scout']['status'])->toBe('disabled');
        });
    });

    describe('getSystemStatus', function (): void {
        test('returns expected system information', function (): void {
            config([
                'app.env' => 'testing',
                'app.debug' => true,
                'app.timezone' => 'UTC',
                'app.locale' => 'lt',
                'app.url' => 'http://localhost',
            ]);

            $status = $this->service->getSystemStatus();

            expect($status)
                ->php_version->toBe(PHP_VERSION)
                ->laravel_version->toBe(app()->version())
                ->environment->toBe('testing')
                ->debug_mode->toBeTrue()
                ->timezone->toBe('UTC')
                ->locale->toBe('lt')
                ->url->toBe('http://localhost')
                ->and($status)->toHaveKeys(['memory_limit', 'max_execution_time', 'upload_max_filesize', 'disk_space']);
        });
    });

    describe('getTypesenseStatus', function (): void {
        test('returns unconfigured when Typesense API key is missing', function (): void {
            config(['scout.typesense.client-settings.api_key' => null]);

            $status = $this->service->getTypesenseStatus();

            expect($status)
                ->status->toBe('unconfigured')
                ->configured->toBeFalse()
                ->enabled->toBeFalse();
        });

        test('returns disabled when configured but no collections', function (): void {
            config(['scout.typesense.client-settings.api_key' => 'test-key']);
            config(['scout.typesense.model-settings' => []]);

            $status = $this->service->getTypesenseStatus();

            expect($status)
                ->status->toBe('disabled')
                ->configured->toBeTrue()
                ->enabled->toBeFalse();
        });
    });
});
