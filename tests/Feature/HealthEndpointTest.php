<?php

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

/**
 * The /up health endpoint is registered by withRouting(health:) in bootstrap/app.php —
 * before routes/web.php loads, so the catch-all {permalink} route cannot swallow it, and
 * excepted from PreventRequestsDuringMaintenance so load-balancer checks survive `down`.
 *
 * Note: the pre-autoloader fallback (storage/framework/maintenance.php, see
 * deployment/maintenance.php) still intercepts /up during the deploy-window variant of
 * maintenance mode — that is a deliberate trade-off, not covered here.
 */
test('answers the health endpoint with the built-in health view', function (): void {
    Event::fake([DiagnosingHealth::class]);

    $this->get('/up')
        ->assertStatus(200)
        ->assertSee('Application up');

    Event::assertDispatched(DiagnosingHealth::class);
});

test('answers the health endpoint as JSON for API clients', function (): void {
    Event::fake([DiagnosingHealth::class]);

    $this->getJson('/up')->assertStatus(200)->assertExactJson(['status' => 'up']);

    Event::assertDispatched(DiagnosingHealth::class);
});

test('stays reachable while the site is down for maintenance', function (): void {
    Event::fake([DiagnosingHealth::class]);
    Artisan::call('down');

    $this->get('/up')->assertStatus(200);
    $this->get('/')->assertStatus(503);

    Event::assertDispatched(DiagnosingHealth::class);
});

/**
 * Always leave maintenance mode, even when an expectation above fails — a leftover
 * storage/framework/down file would break every subsequent test and local dev.
 */
afterEach(function (): void {
    Artisan::call('up');
});
