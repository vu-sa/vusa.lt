<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('applies reader preferences from the admin appearance menu', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->navigate('/mano/institutions');
    waitForInertiaRender($page);

    $initialSize = (float) $page->script('getComputedStyle(document.documentElement).fontSize');
    $initialBorder = $page->script('getComputedStyle(document.documentElement).getPropertyValue("--border")');

    $page->click('[data-slot="account-menu-trigger"]');
    $page->page()->locator('[data-slot="appearance-settings-trigger"]')->hover();
    $page->click('[data-slot="accessibility-settings-open"]');
    $page->assertVisible('[data-slot="admin-accessibility-dialog"]');

    $page->click('[aria-label="Padidinti teksto dydį"]');
    expect((float) $page->script('getComputedStyle(document.documentElement).fontSize'))->toBeGreaterThan($initialSize);

    $page->click('[data-slot="accessibility-settings"] [role="checkbox"]:first-of-type');
    expect($page->script('getComputedStyle(document.documentElement).getPropertyValue("--border")'))->not->toBe($initialBorder);

    $page->click('[data-slot="accessibility-settings"] [role="checkbox"]:last-of-type');
    expect($page->script('getComputedStyle(document.querySelector("a")).textDecorationLine'))->toContain('underline');

    $page->assertNoJavaScriptErrors();
});
