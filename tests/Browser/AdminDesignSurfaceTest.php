<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Mirrors PublicDesignSurfaceTest.php for the admin surface (.ai/redesign/admin, PR 2.1).
 * Reaching [data-surface="admin"] depends on:
 *
 * 1. App\Support\DesignSurface / app.blade.php stamping the attribute on <html> for a user who
 *    opted in (covered server-side by tests/Feature/Public/DesignSurfaceTest.php);
 * 2. the compiled CSS actually resolving through it, and admin/surface.css being imported
 *    *before* public/surface.css so a nested rich-content preview still wins its own palette.
 *
 * (2) is the one that needs a browser.
 */
it('resolves the admin token scope in the browser for an opted-in user', function (): void {
    $user = makeUser(Tenant::query()->first());
    $user->setNewAdminShellEnabled(true);

    $page = loginAsAdmin($user);

    // `<html data-surface>` is stamped by app.blade.php on the *document* request. The
    // post-login redirect is an Inertia visit (LoginForm.vue's `form.post()`), which is an
    // SPA/XHR navigation that never touches <html> — so right after login the attribute still
    // reflects the login page's own (unauthenticated, no flag) render. A real hard navigation
    // (any subsequent reload, a bookmark, a new tab) picks it up; force one here rather than
    // asserting on the transient post-login state, which is not what a returning visit sees.
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

    expect($borderRadius)->toBe('0px');

    expect($page->script('getComputedStyle(document.documentElement).fontFamily'))
        ->toContain('Atkinson Hyperlegible Next');

    // admin.css's `:root { font-size: 90% }` must not win over the surface's 100% reset — the
    // whole point of PR 2.1's `html[data-surface="admin"]` rule.
    expect($page->script('getComputedStyle(document.documentElement).fontSize'))
        ->toBe('16px');

    $page->assertNoJavaScriptErrors();
});

it('leaves a non-opted-in admin on the legacy surface in the browser', function (): void {
    $user = makeUser(Tenant::query()->first());

    $page = loginAsAdmin($user);

    expect($page->script('document.documentElement.getAttribute("data-surface")'))
        ->toBeNull();

    $page->assertNoJavaScriptErrors();
});
