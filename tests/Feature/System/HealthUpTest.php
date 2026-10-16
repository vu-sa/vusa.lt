<?php

use App\Listeners\DiagnoseHealth;
use App\Services\ServiceProbes;
use App\Services\SystemMonitorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Mockery\MockInterface;

/**
 * `/up` is what the external uptime monitor pings, so it must fail (500) whenever a service the
 * site depends on is down. The monitor and probes are mocked: they leave the process.
 */
beforeEach(function (): void {
    config(['app.debug' => false]);
    Cache::forget(DiagnoseHealth::CACHE_KEY);
});

/**
 * @param  array<string, mixed>  $overrides  check name => status string (or bool/float for typesense/disk)
 */
function servicesReporting(array $overrides = []): MockInterface
{
    $values = [
        'database' => 'healthy',
        'redis' => 'healthy',
        'typesense' => true,
        'reverb' => 'healthy',
        'scheduler' => 'healthy',
        'queue' => 'healthy',
        'disk' => 40.0,
        ...$overrides,
    ];

    test()->mock(SystemMonitorService::class, function (MockInterface $mock) use ($values): void {
        $mock->allows('getDatabaseStatus')->andReturn(['status' => $values['database']]);
        $mock->allows('getRedisStatus')->andReturn(['status' => $values['redis']]);
        $mock->allows('getSchedulerStatus')->andReturn(['status' => $values['scheduler']]);
        $mock->allows('getQueueStatus')->andReturn(['status' => $values['queue']]);
    });

    return test()->mock(ServiceProbes::class, function (MockInterface $mock) use ($values): void {
        $mock->allows('typesenseIsUp')->andReturn($values['typesense']);
        $mock->allows('reverbStatus')->andReturn(['status' => $values['reverb']]);
        $mock->allows('diskUsedPercent')->andReturn($values['disk']);
    });
}

test('is up when every check passes', function (): void {
    servicesReporting();

    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
});

test('is up when Reverb is disabled', function (): void {
    servicesReporting(['reverb' => 'disabled']);

    $this->getJson('/up')->assertOk();
});

test('is down and names the failing check', function (string $check, mixed $value): void {
    Exceptions::fake();
    servicesReporting([$check => $value]);

    $this->getJson('/up')->assertInternalServerError()->assertExactJson(['status' => 'down']);

    Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === "Health check failed: {$check}");
})->with([
    'database' => ['database', 'error'],
    'redis' => ['redis', 'error'],
    'typesense' => ['typesense', false],
    'reverb' => ['reverb', 'error'],
    'stale scheduler' => ['scheduler', 'warning'],
    'stale queue' => ['queue', 'warning'],
    'full disk' => ['disk', 95.0],
]);

test('does not leak the failing check names publicly', function (): void {
    Exceptions::fake();
    servicesReporting(['typesense' => false]);

    $this->get('/up')->assertInternalServerError()->assertDontSee('typesense');
});

test('reuses the result for 30 seconds instead of probing on every request', function (): void {
    $probes = servicesReporting();

    $this->getJson('/up')->assertOk();
    $this->getJson('/up')->assertOk();

    $probes->shouldHaveReceived('typesenseIsUp')->once();
});
