<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the floating Gantt date below the shell topbar', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->resize(1180, 500);
    $page->navigate('/mano/dashboard/atstovavimas');
    waitForInertiaRender($page, '[data-tour=visak-timeline]');
    // The timeline renders only once in view, and deferred panels landing above it can push it back out on a slow runner.
    $deadline = microtime(true) + 15;

    while (! $page->script('document.querySelector("[data-tour=visak-timeline]").scrollIntoView() ?? !! document.querySelector(".gantt-center-date")') && microtime(true) < $deadline) {
        usleep(250_000);
    }

    $page->page()->waitForSelector('.gantt-center-date', ['timeout' => 1_000]);

    $stacking = $page->script('(() => {
        const scroll = document.querySelector("[data-slot=admin-scroll-area]");
        const shell = scroll.querySelector(":scope > .sticky");
        const badge = document.querySelector(".gantt-center-date");
        const chart = document.querySelector("[data-slot=meetings-gantt]");
        scroll.scrollTop += badge.getBoundingClientRect().top - shell.getBoundingClientRect().bottom + 5;
        const rect = badge.getBoundingClientRect();
        const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + 2);
        return {
            chartIsolated: getComputedStyle(chart).isolation === "isolate",
            badgeAboveAxis: rect.bottom <= chart.getBoundingClientRect().top + 1,
            shellOnTop: shell.contains(hit),
        };
    })()');

    expect($stacking)->toBe([
        'chartIsolated' => true,
        'badgeAboveAxis' => true,
        'shellOnTop' => true,
    ]);
});

/**
 * Full screen used to be a modal dialog, which made every other portal inert: the create
 * meeting window and the legend opened from the chart landed behind it. It is now the
 * section itself pinned over the shell, so a dialog opened from it must end up on top.
 */
it('keeps dialogs opened from the full-screen timeline above it', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->resize(1180, 800);
    $page->navigate('/mano/dashboard/atstovavimas');
    waitForInertiaRender($page, '[data-tour=visak-timeline]');

    // The chart renders only once scrolled into view, as in the test above.
    $deadline = microtime(true) + 15;

    while (! $page->script('document.querySelector("[data-tour=visak-timeline]").scrollIntoView() ?? !! document.querySelector("[data-tour=gantt-fullscreen]")') && microtime(true) < $deadline) {
        usleep(250_000);
    }

    $page->click('[data-tour="gantt-fullscreen"]');
    waitForInertiaRender($page, '[data-slot="focus-mode-frame"][data-active]');

    expect($page->script('(() => {
        const frame = document.querySelector("[data-slot=focus-mode-frame]");
        return frame.contains(document.elementFromPoint(4, 4)) && frame.contains(document.elementFromPoint(window.innerWidth - 4, window.innerHeight - 4));
    })()'))->toBeTrue();

    $page->click('[data-slot="focus-mode-frame"] [data-tour="gantt-legend"]');
    waitForInertiaRender($page, '[data-slot="dialog-content"]');

    expect($page->script('(() => {
        const dialog = document.querySelector("[data-slot=dialog-content]");
        const box = dialog.getBoundingClientRect();
        return dialog.contains(document.elementFromPoint(box.left + box.width / 2, box.top + 24));
    })()'))->toBeTrue()
        ->and($page->script('!!document.querySelector("[data-slot=focus-mode-frame][data-active]")'))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});
