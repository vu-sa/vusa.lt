<?php

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The admin shell. What only a browser can settle: that the
 * catalog's route matching lights the right workspace and tab on a real page, that the bottom bar
 * exists on a phone but not on a desktop, and that none of it throws.
 */
function openShell(int $width, int $height, string $path = '/mano/institutions', ?Closure $arrange = null): mixed
{
    $user = makeAdminUser(Tenant::query()->first());

    if ($arrange) {
        $arrange($user);
    }

    $page = loginAsAdmin($user);
    $page->resize($width, $height)->navigate($path);
    waitForInertiaRender($page);

    return $page;
}

it('shows the workspace and section a page belongs to', function (): void {
    $page = openShell(1440, 900);

    $page->assertPresent('[data-slot=admin-shell]')->assertPresent('[data-slot=section-tabs]');

    expect($page->script("document.querySelector('[data-slot=workspace-picker] button').textContent"))->toContain('ViSAK')
        ->and($page->script("document.querySelector('[data-slot=section-tabs] [aria-current=page]').textContent"))->toContain('Institucijos');

    $page->assertNoJavaScriptErrors();
});

it('keeps the tab lit on a record page, not only on the index', function (): void {
    $page = openShell(1440, 900, '/mano/institutions/create');

    expect($page->script("document.querySelector('[data-slot=section-tabs] [aria-current=page]').textContent"))->toContain('Institucijos');
});

it('uses the bottom bar and hides the create button in the top bar on a phone', function (): void {
    $page = openShell(390, 844);

    expect($page->script("getComputedStyle(document.querySelector('[data-slot=mobile-bottom-bar]')).display"))->toBe('flex')
        ->and($page->script("document.querySelector('[data-slot=shell-top-bar] button.bg-brand-fill').offsetParent"))->toBeNull();
});

it('has no bottom bar and shows the create button in the top bar on a desktop', function (): void {
    $page = openShell(1440, 900);

    expect($page->script("getComputedStyle(document.querySelector('[data-slot=mobile-bottom-bar]')).display"))->toBe('none')
        ->and($page->script("document.querySelector('[data-slot=shell-top-bar] button.bg-brand-fill').offsetParent"))->not->toBeNull();
});

it('opens the action window from the shell create button', function (): void {
    $page = openShell(1440, 900);

    $page->click('[data-slot=shell-top-bar] [data-tour=action-create]');

    waitForInertiaRender($page, '[data-slot=action-window-screen]');
    $page->assertNoJavaScriptErrors();
});

it('draws no breadcrumbs on a section index — the tabs already say where you are', function (): void {
    $page = openShell(1440, 900);

    expect($page->script("document.querySelector('[data-slot=shell-breadcrumbs]')"))->toBeNull();
});

it('draws breadcrumbs below section level, starting with the section as the way back', function (): void {
    $page = openShell(1440, 900, '/mano/news/create');

    expect($page->script("document.querySelector('[data-slot=shell-breadcrumbs] ol a').textContent.trim()"))->toBe('Naujienos')
        ->and($page->script("document.querySelector('[data-slot=shell-breadcrumbs] [aria-current=page]').textContent.trim()"))->toBe('Nauja naujiena');
    $page->assertNoJavaScriptErrors();
});

it('counts pending tasks as a badge on the picker instead of a top-bar indicator', function (): void {
    $page = openShell(1440, 900, '/mano/institutions', function ($user): void {
        Task::factory()->create(['due_date' => now()->subDay()])->users()->attach($user->id);
    });

    expect($page->script("document.querySelector('[data-slot=workspace-picker] [data-slot=task-count-badge]').dataset.statusRole"))->toBe('danger')
        ->and($page->script("document.querySelector('[data-tour=tasks-indicator]')"))->toBeNull();
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
