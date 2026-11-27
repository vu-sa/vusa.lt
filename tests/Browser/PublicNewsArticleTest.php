<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The article masthead needs a real browser for the two things that fail silently everywhere else.
 *
 * 1. **The band's ground.** `.band-masthead` is a theme-aware pair of rules, and jsdom resolves no
 *    stylesheet — a component test can only read the class name back. Whether the band is a warm
 *    tint on paper and the near-black `--ink` slab on dark is a question only the cascade answers.
 * 2. **The full-bleed break-out.** `.rc-viewport` escapes the rc-canvas, the `.wrapper` grid *and*
 *    PublicLayout's `.container` by way of `margin-inline: calc(50% - 50vw)`. Both halves of that
 *    (the escape, and `overflow-x: clip` absorbing the half-scrollbar overhang) fail without an
 *    error: the band simply stops at the content measure, or the page gains a horizontal scrollbar.
 */
function seedArticle(): News
{
    return News::factory()->for(Tenant::main())->create([
        'lang' => 'lt',
        'title' => 'Pradedama kandidatų registracija',
        'permalink' => 'pradedama-kandidatu-registracija',
        'draft' => false,
        'publish_time' => now()->subDay(),
    ]);
}

it('renders the article masthead as a full-bleed band with no horizontal overflow', function (): void {
    seedArticle();

    $page = visitPublicSubdomain('www', '/lt/naujiena/pradedama-kandidatu-registracija');

    $page->assertSee('Pradedama kandidatų registracija');

    $geometry = $page->script(<<<'JS'
        (() => {
          const band = document.querySelector('[data-slot="news-article"] > header');
          const rect = band.getBoundingClientRect();

          return {
            width: Math.round(rect.width),
            viewport: window.innerWidth,
            left: Math.round(rect.left),
            overflows: document.documentElement.scrollWidth > window.innerWidth + 1,
          };
        })()
    JS);

    expect($geometry['width'])->toBe($geometry['viewport'])
        ->and($geometry['left'])->toBe(0)
        ->and($geometry['overflows'])->toBeFalse();

    $page->assertNoJavaScriptErrors();
});

it('paints the masthead darker than the canvas in dark mode', function (): void {
    seedArticle();

    $page = visitPublicSubdomain('www', '/lt/naujiena/pradedama-kandidatu-registracija');

    // Force dark rather than relying on the OS preference — `useDark()` reads this key.
    $page->script("localStorage.setItem('vueuse-color-scheme', 'dark'); document.documentElement.classList.add('dark');");

    $grounds = $page->script(<<<'JS'
        (() => {
          const band = document.querySelector('[data-slot="news-article"] > header');
          const ink = getComputedStyle(document.documentElement).getPropertyValue('--ink').trim();
          const canvas = document.createElement('canvas');
          canvas.width = 1;
          canvas.height = 1;
          const ctx = canvas.getContext('2d');
          const luminance = (color) => {
            ctx.clearRect(0, 0, 1, 1);
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, 1, 1);
            const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
          };
          const bandColor = getComputedStyle(band).backgroundColor;
          const bodyColor = getComputedStyle(document.body).backgroundColor;

          return {
            band: bandColor,
            body: bodyColor,
            bandLuminance: luminance(bandColor),
            bodyLuminance: luminance(bodyColor),
            inkDefined: ink.length > 0,
          };
        })()
    JS);

    expect($grounds['inkDefined'])->toBeTrue()
        ->and($grounds['band'])->not->toBe($grounds['body'])
        ->and($grounds['bandLuminance'])->toBeLessThan($grounds['bodyLuminance']);

    $page->assertNoJavaScriptErrors();
});
