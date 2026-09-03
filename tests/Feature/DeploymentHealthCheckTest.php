<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

it('checks the dedicated health endpoint by default', function (): void {
    config(['app.url' => 'https://www.naujas.vusa.lt']);
    Http::fake([
        'https://www.naujas.vusa.lt/up' => Http::response(status: 200),
    ]);
    Sleep::fake();

    $this->artisan('deployment:health-check', ['--retries' => 1])
        ->assertExitCode(0);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.naujas.vusa.lt/up');
});

it('keeps the health endpoint reachable behind staging basic auth', function (): void {
    config([
        'app.env' => 'staging',
        'app.staging_user' => 'staging-user',
        'app.staging_password' => 'staging-password',
    ]);

    $this->get('/up')->assertOk();
});
