<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The admin shell's geometry and global shortcuts. Which workspace and tab light up is covered
 * by the Vitest shell tests; ActionWindowTest opens the create button.
 */
function openShell(int $width, int $height, string $path = '/mano/institutions'): mixed
{
    $user = makeAdminUser(Tenant::query()->first());

    $page = loginAsAdmin($user);
    $page->resize($width, $height)->navigate($path);
    waitForInertiaRender($page);

    return $page;
}

it('swaps the top-bar create button and bell for the bottom bar on a phone', function (): void {
    $page = openShell(1440, 900);

    expect($page->script("getComputedStyle(document.querySelector('[data-slot=mobile-bottom-bar]')).display"))->toBe('none')
        ->and($page->script("document.querySelector('[data-slot=shell-top-bar] button.bg-brand-fill').offsetParent"))->not->toBeNull();

    $page->resize(390, 844);

    expect($page->script("getComputedStyle(document.querySelector('[data-slot=mobile-bottom-bar]')).display"))->toBe('flex')
        ->and($page->script("document.querySelector('[data-slot=shell-top-bar] button.bg-brand-fill').offsetParent"))->toBeNull()
        ->and($page->script("document.querySelector('[data-slot=shell-top-bar] [data-tour=notifications-indicator]').offsetParent"))->toBeNull();
});

it('opens only the active workspace in the phone menu and switches sections when tapped', function (): void {
    $page = openShell(390, 844);
    $page->click('[data-tour=mobile-menu]');

    $workspaceState = 'Array.from(document.querySelectorAll("[data-slot=mobile-menu-workspace]"), workspace => ({
        label: workspace.querySelector("button").textContent.trim(),
        expanded: workspace.querySelector("button").getAttribute("aria-expanded"),
        visible: getComputedStyle(workspace.querySelector("ul")).display !== "none",
    }))';

    $initial = $page->script($workspaceState);
    expect(array_values(array_filter($initial, fn (array $workspace): bool => $workspace['visible'])))->toHaveCount(1)
        ->and($initial[array_search('true', array_column($initial, 'expanded'), true)]['label'])->toContain('ViSAK');

    $page->click('[data-slot=mobile-menu-workspace]:first-child > button');

    $switched = $page->script($workspaceState);
    expect($switched[0]['expanded'])->toBe('true')
        ->and($switched[0]['visible'])->toBeTrue()
        ->and(array_values(array_filter($switched, fn (array $workspace): bool => $workspace['visible'])))->toHaveCount(1);

    $page->assertNoJavaScriptErrors();
});

it('opens the keyboard shortcut guide with ?', function (): void {
    $page = openShell(1440, 900);

    $page->script("window.dispatchEvent(new KeyboardEvent('keydown', { key: '?', cancelable: true }))");

    $page
        ->assertSee('Klaviatūros trumpiniai')
        ->assertNoJavaScriptErrors();
});

it('focuses a collection search with /', function (): void {
    $page = openShell(1440, 900);

    $page->script("window.dispatchEvent(new KeyboardEvent('keydown', { key: '/', cancelable: true }))");

    expect($page->script("document.activeElement?.hasAttribute('data-admin-collection-search')"))->toBeTrue();
});
