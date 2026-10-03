<?php

use App\Models\Banner;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('previews the banner image and changes its status in the collection', function (): void {
    $tenant = Tenant::query()->first();
    $user = makeAdminUser($tenant);
    $banner = Banner::factory()->for($tenant)->create([
        'title' => 'Banerio vizualas',
        'image_url' => '/images/placeholders/foto1.jpg',
        'is_active' => 1,
    ]);
    Banner::factory()->for($tenant)->create(['title' => 'Paslėptas baneris', 'is_active' => 0]);

    $page = loginAsAdmin($user);
    $page->navigate('/mano/banners');
    waitForInertiaRender($page, '[data-slot=collection-table]');

    $page->page()->locator('button[aria-label*="Banerio vizualas"]')->hover();
    $page->assertVisible('[data-slot=hover-card-content]');
    expect($page->script('document.querySelector("[data-slot=hover-card-content] img").getBoundingClientRect().width'))
        ->toBeGreaterThan(100);
    $page->screenshot(fullPage: false, filename: 'banner-index-hover');

    $page->click('[data-slot=collection-quick-filters] button:has-text("Aktyvus")');
    $page->assertSee('Rasta 1');

    $page->click('[data-slot=collection-status-menu]');
    $page->click('[role=menuitemradio]:has-text("Neaktyvus")');
    $page->assertPresent('[data-slot=collection-status-menu][aria-label="Keisti būseną: Neaktyvus"]');
    $this->assertDatabaseHas('banners', ['id' => $banner->id, 'is_active' => 0]);

    // Chromium emits this ResizeObserver warning during table layout without an application exception.
    $page->script('window.__pestBrowser.jsErrors = window.__pestBrowser.jsErrors.filter(error => !error.message.startsWith("ResizeObserver loop completed"))');
    $page->assertNoJavaScriptErrors();
});
