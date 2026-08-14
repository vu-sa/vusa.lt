<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Real-browser regression coverage for the laravel/head migration (see
 * PublicController::applyPageHead()): Inertia's `serverHead: true` must keep adopting and
 * swapping the <title> / <link rel="canonical"> that Laravel Head renders on every SPA
 * navigation, not just on the initial hard load. This is the exact behavior the previous
 * ralphjsmit/laravel-seo setup silently lost once Inertia v3 renamed its ownership attribute
 * to `data-inertia` (the old code stamped a stale `inertia=""` attribute — see
 * PublicControllerSeoTest.php's "Inertia head adoption" group for the server-rendered-HTML
 * side of that same regression).
 */
it('swaps title and canonical URL on client-side navigation between public pages', function (): void {
    $mainTenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $news = News::factory()->create([
        'tenant_id' => $mainTenant->id,
        'title' => 'Browser Test Navigation Article',
        'permalink' => 'browser-test-navigation-article',
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);

    $page = visitPublicSubdomain('www', '/lt/naujienos');

    $page->assertSee($news->title)
        ->assertTitleContains('Naujienų archyvas');

    // Click the card's "read more" affordance rather than the headline text — the headline
    // also appears in the card image's alt attribute, an ambiguous click target. This is a
    // real client-side Inertia visit (no full page reload): if Laravel Head's page-managed
    // elements weren't correctly adopted, the assertions below would still see the archive
    // page's title/canonical after this click, not the article's.
    $page->click('a[href*="browser-test-navigation-article"]')
        ->assertPathContains('browser-test-navigation-article')
        ->assertTitleContains($news->title);

    $canonicalHref = $page->script("document.querySelector('link[rel=canonical]')?.getAttribute('href')");
    expect($canonicalHref)->toContain('browser-test-navigation-article');

    $page->assertNoJavaScriptErrors();
});
