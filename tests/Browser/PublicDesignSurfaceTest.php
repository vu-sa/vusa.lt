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
