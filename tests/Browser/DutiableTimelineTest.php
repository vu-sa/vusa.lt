<?php

use App\Models\Cadence;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The one part of the timeline editor no Vitest run can reach.
 *
 * A bar's geometry only exists once a real layout engine has given the chart a width and
 * d3 has drawn into it, so "drag two columns right and the day of month survives" — the
 * rule the whole editing model rests on — is unobservable in jsdom. Everything around it
 * (staging, the operation list, the planner) is covered there; this covers the gesture.
 *
 * Pointer events are dispatched from inside the page rather than driven through
 * Playwright's mouse API: the plugin exposes no raw mouse, and `dragTo()` wants a target
 * element a chart has no meaningful equivalent of. They are the same events a real
 * pointer produces, against the real SVG the real renderer built.
 */
beforeEach(function (): void {
    $tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys', 'users.read.padalinys']);

    $this->admin = makeUser($tenant);
    $this->duty = $this->admin->duties()->first();
    $this->duty->assignRole('Communication Coordinator');

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
 * Selects the drag subject's bar, then drags its body horizontally by `$dx` pixels.
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

      return 'ok';
    })()
    JS;
}

it('moves a bar by whole months and keeps the day of month', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate(route('duties.show', $this->duty, absolute: false));
    waitForInertiaRender($page, 'button:has-text("Tvarkyti laikotarpius")');

    $page->click('button:has-text("Tvarkyti laikotarpius")');

    // The chart mounts only once the dialog opens, and draws on the frame after that.
    // Waited on by row id: several bars share the class, and Playwright's strict mode
    // refuses a multi-match.
    waitForInertiaRender($page, sprintf('g.dutiable-bar[data-row-id="%s"]', $this->row->id));

    // Two default month columns' worth of pixels; the delta is rounded, so exactness is
    // not required, only that it lands nearer two columns than one or three.
    expect($page->script(dragSubjectBar($this->row->id, 128)))->toBe('ok');

    waitForInertiaRender($page, '[data-slot="dutiable-timeline-dirty-bar"]');

    // 2024-05-18 moved two columns right is the 18th of July, never the 1st. This is the
    // guarantee that an unrelated drag cannot destroy a deliberately off-boundary date.
    expect($page->script('document.querySelector("#selection-start")?.value ?? null'))
        ->toBe('2024-07-18');
});

/**
 * A collapsed group draws a merged bar in place of the rows it hides — d3 geometry again,
 * and the whole point of collapsing is that the rows are gone, so nothing else can assert it.
 */
it('summarises a collapsed duty instead of leaving an empty lane', function (): void {
    foreach (range(1, 3) as $index) {
        Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);
    }

    $page = loginAsAdmin($this->admin);

    $page->navigate(route('dutiables.timeline', ['institution' => $this->duty->institution_id], absolute: false));

    // The collapse-all control lives above the label column, not out in the toolbar.
    // By label, not by position: the strip also holds the sort menu.
    $collapseAll = '[data-tour="timeline-controls"] button[aria-label="Suskleisti visus"]';
    waitForInertiaRender($page, $collapseAll);
    $page->click($collapseAll);

    waitForInertiaRender($page, 'rect.collapsed-group-bar');

    $summary = $page->script(<<<'JS'
    (() => {
      const bar = document.querySelector('rect.collapsed-group-bar');
      const count = document.querySelector('[data-slot="group-row-count"]');

      return {
        width: bar ? Number(bar.getAttribute('width')) : null,
        count: count ? count.textContent.trim() : null,
        duration: count?.nextElementSibling?.textContent.trim() ?? null,
      };
    })()
    JS);

    expect($summary['width'])->toBeGreaterThan(2)
        // Every row and every collapsed header says how long it ran, in at most two units.
        ->and($summary['duration'])->toMatch('/^\d+ (m\.|mėn\.|d\.)/')
        // Five rows on this duty: the three above, the drag subject, and the admin's own
        // seat — makeUser() attaches them to the duty it creates.
        ->and($summary['count'])->toBe('5');
});

/**
 * The chart caps itself at `header + rows`, so whether that arithmetic is right is only
 * observable once a real scrollbar has taken its strip out of the container. jsdom lays
 * nothing out and reports every dimension as 0, which is precisely the case that hid the
 * missing allowance for months.
 */
it('fits a short chart without a vertical scrollbar', function (): void {
    // Three rows: nowhere near enough to need scrolling, which was true before the fix too.
    foreach (range(1, 3) as $index) {
        Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);
    }

    $page = loginAsAdmin($this->admin);

    $page->navigate(route('dutiables.timeline', ['institution' => $this->duty->institution_id], absolute: false));
    waitForInertiaRender($page, '[data-slot="dutiable-gantt"] svg');

    $overflow = $page->script(<<<'JS'
    (() => {
      const chart = document.querySelector('[data-slot="dutiable-gantt"]');
      // The scroller is the only element in the chart that scrolls on both axes.
      const scroller = chart?.querySelector('.overflow-auto');
      if (!scroller) return null;

      return {
        vertical: scroller.scrollHeight - scroller.clientHeight,
        horizontal: scroller.scrollWidth > scroller.clientWidth,
      };
    })()
    JS);

    expect($overflow)->not->toBeNull()
        // The horizontal scrollbar is the whole point of the chart and must still be there;
        // it is what used to eat the last lane.
        ->and($overflow['horizontal'])->toBeTrue()
        ->and($overflow['vertical'])->toBe(0);
});

/**
 * The dock is `sticky bottom-0`, which only means anything once the page can scroll past
 * it — exactly the case jsdom cannot model, and exactly the case the old non-sticky bar
 * failed in.
 */
it('keeps the save controls on screen when the chart is taller than the viewport', function (): void {
    // Enough rows to push the chart well past one screen.
    foreach (range(1, 40) as $index) {
        Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);
    }

    $page = loginAsAdmin($this->admin);

    $page->navigate(route('dutiables.timeline', ['institution' => $this->duty->institution_id], absolute: false));
    waitForInertiaRender($page, '[data-slot="dutiable-timeline-dock"]');

    $visible = $page->script(<<<'JS'
    (() => {
      const dock = document.querySelector('[data-slot="dutiable-timeline-dock"]');
      if (!dock) return false;

      window.scrollTo(0, document.body.scrollHeight);
      const box = dock.getBoundingClientRect();

      return box.top < window.innerHeight && box.bottom > 0;
    })()
    JS);

    expect($visible)->toBeTrue();
});

it('draws a notch for a start date that is not on a month boundary', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate(route('duties.show', $this->duty, absolute: false));
    waitForInertiaRender($page, 'button:has-text("Tvarkyti laikotarpius")');

    $page->click('button:has-text("Tvarkyti laikotarpius")');
    waitForInertiaRender($page, sprintf('g.dutiable-bar[data-row-id="%s"]', $this->row->id));

    // The off-boundary marks are how drift reads at a glance; a chart that renders bars
    // but silently drops them would still pass every jsdom test.
    expect($page->script('document.querySelectorAll("g.off-boundary").length'))->toBeGreaterThan(0);
    expect($page->script('document.querySelectorAll("g.cadence-bands rect.cadence-band").length'))->toBeGreaterThan(0);
});
