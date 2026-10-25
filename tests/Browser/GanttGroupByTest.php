<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The trigger used to be icon-sized and sat entirely under the spotlight's corner notch, so a click
 * opened nothing. A real pointer is the only way to prove what lies on top.
 */
it('opens the grouping menu from the timeline toolbar and switches the grouping', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->resize(1180, 800);
    $page->navigate('/mano/dashboard/atstovavimas');
    waitForInertiaRender($page, '[data-tour=visak-timeline]');

    // The chart renders only once scrolled into view (see GanttDateStackingTest).
    $page->assertScript('(() => { document.querySelector("[data-tour=visak-timeline]").scrollIntoView(); return !! document.querySelector("[data-slot=gantt-group-by]"); })()', true);

    $page->click('[data-slot="gantt-group-by"]');
    $page->page()->waitForSelector('[role="menu"] [role="menuitemradio"] >> nth=1', ['timeout' => 2_000]);
    $page->click('[role="menu"] [role="menuitemradio"] >> nth=1');

    $page->assertScript('document.querySelector("[data-slot=gantt-group-by]").getAttribute("aria-label")', 'Grupuoti: Tipai');
    $page->assertNoJavaScriptErrors();
});
