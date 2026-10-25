<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Mirrors PublicDesignSurfaceTest.php for admin pages: the attribute is stamped on a hard
 * navigation and the shared radius scale and typeface resolve in a real browser.
 */
it('resolves the admin token scope in the browser', function (): void {
    $user = makeUser(Tenant::query()->first());

    // loginAsAdmin() lands with a hard load, which is when app.blade.php stamps `<html data-surface>`.
    $page = loginAsAdmin($user);

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

    // The admin surface's 100% reset keeps the root at a readable size.
    expect($page->script('getComputedStyle(document.documentElement).fontSize'))
        ->toBe('16px');

    $page->assertNoJavaScriptErrors();
});

it('shows a focus outline on admin links', function (): void {
    $page = loginAsAdmin(makeUser(Tenant::query()->first()), '/mano');
    waitForInertiaRender($page);

    $focus = $page->script(<<<'JS'
        (() => {
          const link = document.querySelector('[data-slot="shell-top-bar"] a');
          link.focus();
          const style = getComputedStyle(link);
          return {
            visible: link.matches(':focus-visible'),
            width: style.outlineWidth,
            style: style.outlineStyle,
          };
        })()
    JS);

    expect($focus)->toMatchArray(['visible' => true, 'width' => '2px', 'style' => 'solid']);
    $page->assertNoJavaScriptErrors();
});
