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

it('keeps the translucent navigation rows over page content while scrolling', function (): void {
    $page = openShell(1440, 900);

    foreach ([1440, 390] as $width) {
        $page->resize($width, 900);

        $geometry = $page->script('(() => {
            const scrollArea = document.querySelector("[data-slot=admin-scroll-area]");
            const bar = document.querySelector("[data-slot=shell-top-bar]");
            const tabs = document.querySelector("[data-slot=section-tabs]");
            const page = document.querySelector("[data-slot=admin-page-measure]");
            const filler = document.createElement("div");
            filler.style.height = "1800px";
            page.append(filler);
            scrollArea.scrollTop = 0;
            const barTop = bar.getBoundingClientRect().top;
            const tabsTop = tabs.getBoundingClientRect().top;
            scrollArea.scrollTop = 350;
            return {
                barTop,
                barTopAfterScroll: bar.getBoundingClientRect().top,
                barBottom: bar.getBoundingClientRect().bottom,
                tabsTop,
                tabsTopAfterScroll: tabs.getBoundingClientRect().top,
                tabsBottom: tabs.getBoundingClientRect().bottom,
                pageTop: page.getBoundingClientRect().top,
                scrollTop: scrollArea.scrollTop,
                barBackdropFilter: getComputedStyle(bar).backdropFilter,
                tabsBackdropFilter: getComputedStyle(tabs).backdropFilter,
            };
        })()');

        expect($geometry['scrollTop'])->toBe(350)
            ->and($geometry['barTopAfterScroll'])->toBe($geometry['barTop'])
            ->and($geometry['tabsTopAfterScroll'])->toBe($geometry['tabsTop'])
            ->and($geometry['pageTop'])->toBeLessThan($geometry['barBottom'])
            ->and($geometry['pageTop'])->toBeLessThan($geometry['tabsBottom'])
            ->and($geometry['barBackdropFilter'])->toContain('blur')
            ->and($geometry['tabsBackdropFilter'])->toContain('blur');
    }
});

it('aligns collection and form shells with the top bar measure', function (): void {
    $page = openShell(1180, 900);

    foreach (['/mano/institutions', '/mano/institutions/create'] as $path) {
        if ($path !== '/mano/institutions') {
            $page->navigate($path);
            waitForInertiaRender($page, '[data-slot=form-page]');
        }

        $alignment = $page->script('(() => {
            const inset = element => {
                const rect = element.getBoundingClientRect();
                const style = getComputedStyle(element);
                return [rect.left + parseFloat(style.paddingLeft), rect.right - parseFloat(style.paddingRight)];
            };
            return {
                bar: inset(document.querySelector("[data-slot=shell-top-bar] > div")),
                page: inset(document.querySelector("[data-slot=admin-page-measure]")),
            };
        })()');

        expect(abs($alignment['bar'][0] - $alignment['page'][0]))->toBeLessThanOrEqual(1)
            ->and(abs($alignment['bar'][1] - $alignment['page'][1]))->toBeLessThanOrEqual(1);
    }
});

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
