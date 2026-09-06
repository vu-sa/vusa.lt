<?php

use App\Models\Navigation;
use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('rewrites production navigation and quick-link URLs to the configured app URL', function (): void {
    config(['app.url' => 'http://www.vusa.test:8080']);

    $navigation = Navigation::factory()->create([
        'url' => 'https://www.vusa.lt/lt/naujienos?category=5#latest',
    ]);
    $quickLink = QuickLink::factory()->for(Tenant::factory())->create([
        'link' => 'https://www.vusa.lt/en/documents?type=report#downloads',
    ]);
    $externalNavigation = Navigation::factory()->create([
        'url' => 'https://example.com/lt/naujienos',
    ]);
    $trashedQuickLink = QuickLink::factory()->for(Tenant::factory())->create([
        'link' => 'https://www.vusa.lt/lt/kontaktai',
    ]);
    $trashedQuickLink->delete();

    $this->artisan('urls:rewrite-vusa')
        ->expectsOutputToContain('Rewrote 1 navigation URL(s) and 2 quick link(s)')
        ->assertSuccessful();

    expect($navigation->refresh()->url)->toBe('http://www.vusa.test:8080/lt/naujienos?category=5#latest')
        ->and($quickLink->refresh()->link)->toBe('http://www.vusa.test:8080/en/documents?type=report#downloads')
        ->and($externalNavigation->refresh()->url)->toBe('https://example.com/lt/naujienos')
        ->and(QuickLink::withTrashed()->findOrFail($trashedQuickLink->id)->link)->toBe('http://www.vusa.test:8080/lt/kontaktai');
});

test('is idempotent after URLs have been rewritten', function (): void {
    config(['app.url' => 'https://www.naujas.vusa.lt']);

    Navigation::factory()->create(['url' => 'https://www.vusa.lt/lt/naujienos']);

    $this->artisan('urls:rewrite-vusa')->assertSuccessful();
    $this->artisan('urls:rewrite-vusa')
        ->expectsOutputToContain('Rewrote 0 navigation URL(s) and 0 quick link(s)')
        ->assertSuccessful();
});

test('refuses to rewrite URLs when the configured app URL has no host', function (): void {
    config(['app.url' => '/']);
    $navigation = Navigation::factory()->create(['url' => 'https://www.vusa.lt/lt/naujienos']);

    $this->artisan('urls:rewrite-vusa')
        ->expectsOutputToContain('must include a host')
        ->assertFailed();

    expect($navigation->refresh()->url)->toBe('https://www.vusa.lt/lt/naujienos');
});
