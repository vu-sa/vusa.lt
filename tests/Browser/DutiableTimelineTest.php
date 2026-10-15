<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The parts of the timeline editor no Vitest run can reach: the drag gesture, the page's
 * height against a real viewport, and full screen over the real shell. What the renderers
 * draw (notches, bands, collapsed summaries) is asserted in `timelineRenderers.test.ts`.
 *
 * Pointer events are dispatched from inside the page rather than driven through
 * Playwright's mouse API: the plugin exposes no raw mouse, and `dragTo()` wants a target
 * element a chart has no meaningful equivalent of. They are the same events a real
 * pointer produces, against the real SVG the real renderer built.
 */
beforeEach(function (): void {
    $tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys', 'users.read.padalinys']);

    $this->admin = makeUser($tenant);
    $this->duty = $this->admin->duties()->first();
    $this->duty->assignRole('Komunikacijos koordinatorius');

    $this->holder = User::factory()->create(['name' => 'Timeline Drag Subject']);

    $this->row = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->holder->id,
        'start_date' => '2024-05-18',
        'end_date' => '2025-05-17',
    ]);

    Cadence::factory()->create([
        'institution_id' => null,
        'start_date' => '2024-07-01',
        'end_date' => '2025-06-30',
    ]);
});

/**
 * Selects the drag subject's bar, then drags its body horizontally by `$dx` pixels, ending
 * with the click a real release produces.
 */
function dragSubjectBar(string $rowId, int $dx): string
{
    return <<<JS
    (() => {
      const bar = document.querySelector('g.dutiable-bar[data-row-id="{$rowId}"]');
      if (!bar) return 'no-bar';

      const box = bar.getBoundingClientRect();
      const y = box.top + box.height / 2;
      // The midpoint of the bar, well clear of both edge handles: a body drag, not a resize.
      const x = box.left + box.width / 2;

      const pointer = (type, clientX, target) => target.dispatchEvent(new PointerEvent(type, {
        bubbles: true, cancelable: true, clientX, clientY: y, button: 0, pointerId: 1,
      }));

      bar.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, clientX: x, clientY: y }));

      pointer('pointerdown', x, bar);
      pointer('pointermove', x + {$dx}, document);
      pointer('pointerup', x + {$dx}, document);
      // A real pointer's release also clicks whatever is under it: the dragged bar.
      bar.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, clientX: x + {$dx}, clientY: y }));

      return 'ok';
    })()
    JS;
}

it('moves a bar by whole months and keeps the day of month', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate(route('duties.show', $this->duty, absolute: false));
    waitForInertiaRender($page, '[data-testid=record-overflow-trigger]');

    $page->click('[data-testid=record-overflow-trigger]');
    $page->click('[role=menuitem]:has-text("Tvarkyti laikotarpius")');

    // The chart mounts only once the dialog opens, and draws on the frame after that.
    // Waited on by row id: several bars share the class, and Playwright's strict mode
    // refuses a multi-match.
    waitForInertiaRender($page, sprintf('g.dutiable-bar[data-row-id="%s"]', $this->row->id));

    // Two default month columns' worth of pixels; the delta is rounded, so exactness is
    // not required, only that it lands nearer two columns than one or three.
    expect($page->script(dragSubjectBar($this->row->id, 128)))->toBe('ok');

    waitForInertiaRender($page, '[data-slot="dutiable-timeline-dirty-bar"][data-dirty]');

    // 2024-05-18 moved two columns right is the 18th of July, never the 1st. This is the
    // guarantee that an unrelated drag cannot destroy a deliberately off-boundary date.
    // Read from the side panel, which also proves the drag kept the bar selected.
    expect($page->script('document.querySelector("#selection-start")?.value ?? null'))
        ->toBe('2024-07-18');
});

/**
 * The editor takes the screen's height and scrolls inside, so the toolbar holding the save
 * controls never leaves view — and a short chart ends after its last lane. Both are sums a
 * real scrollbar takes part in, which jsdom reports as 0.
 */
it('fits the page to the screen and scrolls long charts inside it', function (): void {
    foreach (range(1, 3) as $index) {
        Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);
    }

    $page = loginAsAdmin($this->admin);
    $page->resize(1180, 800);

    $timeline = route('dutiables.timeline', ['institution' => $this->duty->institution_id], absolute: false);
    $page->navigate($timeline);
    waitForInertiaRender($page, '[data-slot="dutiable-gantt"] svg');

    $measure = <<<'JS'
    (() => {
      const scroller = document.querySelector('[data-slot="dutiable-gantt"] .overflow-auto');
      const area = document.querySelector('[data-slot="admin-scroll-area"]');
      const inView = selector => {
        const box = document.querySelector(selector)?.getBoundingClientRect();
        return !!box && box.top >= 0 && box.bottom <= window.innerHeight;
      };

      return {
        chartVertical: scroller.scrollHeight - scroller.clientHeight,
        chartHorizontal: scroller.scrollWidth > scroller.clientWidth,
        pageScrolls: area.scrollHeight - area.clientHeight > 1,
        saveInView: inView('[data-slot="dutiable-timeline-dirty-bar"]'),
        panelInView: inView('[data-slot="dutiable-timeline-side-panel"]'),
      };
    })()
    JS;

    // A short chart ends after its last lane: the horizontal scrollbar, which is the whole
    // point of the chart, must not eat that lane and summon a vertical one.
    expect($page->script($measure))->toMatchArray([
        'chartVertical' => 0,
        'chartHorizontal' => true,
        'pageScrolls' => false,
        'saveInView' => true,
        'panelInView' => true,
    ]);

    foreach (range(1, 40) as $index) {
        Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);
    }

    $page->navigate($timeline);
    waitForInertiaRender($page, '[data-slot="dutiable-gantt"] svg');

    $tall = $page->script($measure);

    expect($tall['chartVertical'])->toBeGreaterThan(0)
        ->and($tall['pageScrolls'])->toBeFalse()
        ->and($tall['saveInView'])->toBeTrue()
        ->and($tall['panelInView'])->toBeTrue();
});

/**
 * Full screen is the chart's own container pinned over the shell, not a modal: popovers
 * and dialogs opened from inside must land above it, and Escape closes them first.
 */
it('takes the chart full screen over the shell and keeps its popovers on top', function (): void {
    $this->seed(DocsSeeder::class);

    $parliament = Institution::query()->where('name->lt', DocsSeeder::TIMELINE_INSTITUTION)->firstOrFail();

    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->resize(1440, 900);

    // Zoomed out so the docs frame shows all three terms rather than the current months only.
    $page->script('localStorage.setItem("dutiable-timeline-view", JSON.stringify({ monthWidthPx: 28, includeEnded: true }))');
    $page->navigate(route('dutiables.timeline', ['institution' => $parliament->id], absolute: false));
    waitForInertiaRender($page, '[data-slot="dutiable-gantt"] svg');

    docsScreenshot($page, 'dutiable-timeline');

    $page->click('[data-tour="timeline-fullscreen"]');
    waitForInertiaRender($page, '[data-slot="focus-mode-frame"][data-active]');

    $covers = <<<'JS'
    (() => {
      const frame = document.querySelector('[data-slot="focus-mode-frame"]');
      const corners = [[4, 4], [window.innerWidth - 4, 4], [4, window.innerHeight - 4], [window.innerWidth - 4, window.innerHeight - 4]];

      return corners.every(([x, y]) => frame.contains(document.elementFromPoint(x, y)));
    })()
    JS;

    // Every corner, the shell's top bar included, now belongs to the chart.
    expect($page->script($covers))->toBeTrue();

    $page->click('[data-slot="focus-mode-frame"] [aria-label="Žymėjimai"]');
    waitForInertiaRender($page, '[data-slot="popover-content"]');

    expect($page->script(<<<'JS'
    (() => {
      const popover = document.querySelector('[data-slot="popover-content"]');
      const box = popover.getBoundingClientRect();

      return popover.contains(document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2));
    })()
    JS))->toBeTrue();

    $escape = 'document.activeElement.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }))';

    // The first Escape closes the popover and leaves full screen alone; the second leaves it.
    $page->script($escape);
    $page->page()->waitForSelector('[data-slot="popover-content"]', ['state' => 'detached', 'timeout' => 3_000]);
    expect($page->script('!!document.querySelector("[data-slot=focus-mode-frame][data-active]")'))->toBeTrue();

    $page->script($escape);
    expect($page->script('!!document.querySelector("[data-slot=focus-mode-frame][data-active]")'))->toBeFalse();

    $page->assertNoJavaScriptErrors();
});

/**
 * The same sheet is a side panel on a desktop and a bottom sheet on a phone, chosen by a
 * live media query — which jsdom does not evaluate.
 */
it('edits a duty period in a side sheet on desktop and a bottom sheet on a phone', function (): void {
    $this->seed(DocsSeeder::class);

    $chair = Duty::query()->where('name->lt', 'Pirmininkas (-ė)')->firstOrFail();

    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->resize(1440, 900);
    $page->navigate(route('duties.show', $chair, absolute: false));
    waitForInertiaRender($page, '[data-testid="member-term-edit"]');

    $page->click('[data-testid="member-term-edit"] >> nth=0');
    waitForInertiaRender($page, '[data-slot="sheet-form"]');

    // Measured once the slide-in has finished; mid-animation the sheet is still off screen.
    $placement = <<<'JS'
    (async () => {
      const sheet = document.querySelector('[data-slot="sheet-form"]');
      await Promise.all(sheet.getAnimations({ subtree: true }).map(animation => animation.finished));
      const box = sheet.getBoundingClientRect();
      return {
        right: Math.round(box.right) === window.innerWidth,
        bottom: Math.round(box.bottom) === window.innerHeight,
        fullWidth: Math.round(box.width) === window.innerWidth,
        title: document.querySelector('[data-slot="sheet-form"] h2')?.textContent.trim() ?? null,
      };
    })()
    JS;

    expect($page->script($placement))->toMatchArray([
        'right' => true,
        'fullWidth' => false,
        'title' => 'Redaguoti pareigybės laikotarpį',
    ]);

    // The duty's discussion panel loads without an error toast (Duty used to be missing
    // from the commentables allowlist, so every duty page 404'd here).
    expect($page->script('document.querySelectorAll("[data-sonner-toast][data-type=error]").length'))->toBe(0);

    // The sheet opens with its first date focused, which reads as an error in a still frame.
    $page->script('document.activeElement?.blur()');
    // Grow the viewport by what the sheet's body scrolls, so the frame is not cut by its footer.
    $hidden = $page->script(<<<'JS'
    [...document.querySelectorAll('[data-slot="sheet-form"] *')]
      .filter(element => ['auto', 'scroll'].includes(getComputedStyle(element).overflowY))
      .reduce((most, element) => Math.max(most, element.scrollHeight - element.clientHeight), 0)
    JS);
    $page->resize(1440, 900 + (int) ceil($hidden) + 24);
    docsScreenshot($page, 'dutiable-sheet', selector: '[data-slot="sheet-form"]');
    $page->resize(1440, 900);

    $page->resize(390, 844);

    expect($page->script($placement))->toMatchArray(['bottom' => true, 'fullWidth' => true])
        ->and($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
});
