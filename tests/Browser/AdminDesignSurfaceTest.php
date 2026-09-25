<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Mirrors PublicDesignSurfaceTest.php for the admin surface.
 * Reaching [data-surface="admin"] depends on:
 *
 * 1. App\Support\DesignSurface / app.blade.php stamping the attribute on <html> for every
 *    Admin/* page (covered server-side by tests/Feature/Public/DesignSurfaceTest.php);
 * 2. the compiled CSS actually resolving through it, and admin/surface.css being imported
 *    *before* public/surface.css so a nested rich-content preview still wins its own palette.
 *
 * (2) is the one that needs a browser.
 */
it('resolves the admin token scope in the browser', function (): void {
    $user = makeUser(Tenant::query()->first());

    $page = loginAsAdmin($user);

    // `<html data-surface>` is stamped by app.blade.php on the *document* request; the post-login
    // redirect is an Inertia visit that never touches <html>, so force a hard navigation rather
    // than asserting on whatever the login page left behind.
    $page->navigate('/mano');
    waitForInertiaRender($page);

    expect($page->script('document.documentElement.getAttribute("data-surface")'))
        ->toBe('admin');

    // Read the resolved custom properties, not the declarations — same reasoning as the public
    // test: this is what the cascade actually produced for this document.
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

    expect($borderRadius)->toBe('0px')
        ->and($page->script('getComputedStyle(document.documentElement).fontFamily'))->toContain('Atkinson Hyperlegible Next');

    // admin.css's `:root { font-size: 90% }` must not win over the surface's 100% reset — the
    // whole point of PR 2.1's `html[data-surface="admin"]` rule.
    expect($page->script('getComputedStyle(document.documentElement).fontSize'))
        ->toBe('16px');

    $page->assertNoJavaScriptErrors();
});
