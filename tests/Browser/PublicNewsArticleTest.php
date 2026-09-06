<?php

use App\Models\Category;
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
    $tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $category = Category::factory()->create(['name' => 'Atstovavimas']);

    return News::factory()->for($tenant)->for($category)->create([
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

          return {
            band: getComputedStyle(band).backgroundColor,
            body: getComputedStyle(document.body).backgroundColor,
            inkDefined: ink.length > 0,
          };
        })()
    JS);

    expect($grounds['inkDefined'])->toBeTrue()
        // The band is a distinct ground, not the page's own — that is the whole point of it.
        ->and($grounds['band'])->not->toBe($grounds['body']);

    $page->assertNoJavaScriptErrors();
});
