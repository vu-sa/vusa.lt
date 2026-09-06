<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The public palette lives in a token scope, `[data-surface="public"]` in resources/css/app.css,
 * and reaching it depends on two things that can each break silently:
 *
 * 1. app.blade.php stamping the attribute on <html> (covered server-side by
 *    tests/Feature/Public/DesignSurfaceTest.php);
 * 2. the compiled CSS actually resolving through it.
 *
 * (2) is the one that needs a browser. The radius scale sits in a plain `@theme` block rather
 * than `@theme inline` precisely so `rounded-lg` emits `var(--radius-lg)` and a scope can zero
 * it — move it back under `inline` and Tailwind bakes `calc(var(--radius) + 4px)` straight into
 * the utility, the override stops applying, and the public site quietly renders with admin's
 * rounded corners. Nothing errors; only a rendered page shows it.
 */
it('resolves the public token scope in the browser', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $page = visitPublicSubdomain('www', '/lt/naujienos');

    expect($page->script('document.documentElement.getAttribute("data-surface")'))
        ->toBe('public');

    // Read the resolved custom properties, not the declarations: this is what the cascade
    // actually produced for this document.
    $radii = $page->script(<<<'JS'
        (() => {
          const s = getComputedStyle(document.documentElement);
          return ['sm', 'md', 'lg', 'xl', '2xl'].map(k => s.getPropertyValue('--radius-' + k).trim());
        })()
    JS);

    expect($radii)->each->toBe('0px');

    // A real element must land at 0 too — proves the utility references the variable rather
    // than an inlined calc().
    $borderRadius = $page->script(<<<'JS'
        (() => {
          const el = document.createElement('div');
          el.className = 'rounded-xl';
          document.body.appendChild(el);
          const r = getComputedStyle(el).borderRadius;
          el.remove();
          return r;
        })()
    JS);

    expect($borderRadius)->toBe('0px');

    $page->assertNoJavaScriptErrors();
});

/**
 * The hero band runs edge to edge, which means escaping PublicLayout's `.container` column.
 * `.rc-viewport` does that with `width: 100vw; margin-inline: calc(50% - 50vw)`, and
 * `overflow-x: clip` on the layout root absorbs the half-scrollbar overhang that leaves.
 *
 * Both halves fail silently: drop the utility and the hero quietly shrinks to the content
 * measure; drop the clip and the whole site gains a horizontal scrollbar. Only a real browser
 * with a real scrollbar can tell you either happened.
 */
it('lets a band escape the content column without making the page scroll sideways', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $page = visitPublicSubdomain('www', '/lt/naujienos');

    $measured = $page->script(<<<'JS'
        (() => {
          const main = document.querySelector('#main-content');
          const probe = document.createElement('div');
          probe.className = 'rc-viewport';
          probe.style.height = '4px';
          main.prepend(probe);

          const rect = probe.getBoundingClientRect();
          const result = {
            probeWidth: Math.round(rect.width),
            viewportWidth: Math.round(window.innerWidth),
            // Centres must agree, or the copy inside the band stops lining up with the header.
            probeCentre: Math.round(rect.left + rect.width / 2),
            viewportCentre: Math.round(window.innerWidth / 2),
            horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
          };
          probe.remove();
          return result;
        })()
    JS);

    expect($measured['probeWidth'])->toBe($measured['viewportWidth'])
        ->and(abs($measured['probeCentre'] - $measured['viewportCentre']))->toBeLessThanOrEqual(1)
        ->and($measured['horizontalOverflow'])->toBeFalse();

    $page->assertNoJavaScriptErrors();
});

it('renders Vue Flow chrome with public tokens and neutral arrows', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $page = visitPublicSubdomain('www', '/lt/naujienos');

    $styles = $page->script(<<<'JS'
        (() => {
          const flow = document.createElement('div');
          flow.className = 'basic-flow';
          flow.innerHTML = `
            <div class="vue-flow__controls"><button class="vue-flow__controls-button">+</button></div>
            <div class="vue-flow__node-default"><button>Node</button></div>
            <div class="vue-flow__handle"></div>
            <svg><path class="vue-flow__edge-path" /></svg>
          `;
          document.body.appendChild(flow);

          const popover = document.createElement('div');
          popover.style.background = 'var(--popover)';
          document.body.appendChild(popover);

          const neutral = document.createElement('span');
          neutral.style.color = 'var(--muted-foreground)';
          document.body.appendChild(neutral);

          const muted = document.createElement('div');
          muted.style.background = 'var(--muted)';
          document.body.appendChild(muted);

          const border = document.createElement('div');
          border.style.border = '1px solid var(--border)';
          document.body.appendChild(border);

          const secondary = document.createElement('div');
          secondary.style.background = 'var(--secondary)';
          document.body.appendChild(secondary);

          const controls = flow.querySelector('.vue-flow__controls');
          const button = flow.querySelector('.vue-flow__controls-button');
          const node = flow.querySelector('.vue-flow__node-default');
          const handle = flow.querySelector('.vue-flow__handle');
          const edge = flow.querySelector('.vue-flow__edge-path');
          const result = {
            controlBackground: getComputedStyle(button).backgroundColor,
            expectedControlBackground: getComputedStyle(popover).backgroundColor,
            controlRadius: getComputedStyle(button).borderRadius,
            controlPadding: getComputedStyle(button).padding,
            controlGap: getComputedStyle(controls).gap,
            arrowStroke: getComputedStyle(edge).stroke,
            expectedArrowStroke: getComputedStyle(neutral).color,
            nodeBorder: getComputedStyle(node).borderColor,
            expectedNodeBorder: getComputedStyle(border).borderColor,
            nodeBackground: getComputedStyle(node).backgroundColor,
            expectedNodeBackground: getComputedStyle(secondary).backgroundColor,
            nodeRadius: getComputedStyle(node).borderRadius,
            handleBorder: getComputedStyle(handle).borderColor,
            expectedHandleBorder: getComputedStyle(neutral).color,
            handleBackground: getComputedStyle(handle).backgroundColor,
            expectedHandleBackground: getComputedStyle(muted).backgroundColor,
          };

          flow.remove();
          popover.remove();
          neutral.remove();
          muted.remove();
          border.remove();
          secondary.remove();

          return result;
        })()
    JS);

    expect($styles['controlBackground'])->toBe($styles['expectedControlBackground'])
        ->and($styles['controlRadius'])->toBe('0px')
        ->and($styles['controlPadding'])->toBe('8px')
        ->and($styles['controlGap'])->toBe('4px')
        ->and($styles['arrowStroke'])->toBe($styles['expectedArrowStroke'])
        ->and($styles['nodeBorder'])->toBe($styles['expectedNodeBorder'])
        ->and($styles['nodeBackground'])->toBe($styles['expectedNodeBackground'])
        ->and($styles['nodeRadius'])->toBe('0px')
        ->and($styles['handleBorder'])->toBe($styles['expectedHandleBorder'])
        ->and($styles['handleBackground'])->toBe($styles['expectedHandleBackground']);

    $page->assertNoJavaScriptErrors();
});
