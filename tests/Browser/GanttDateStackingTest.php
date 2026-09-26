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
