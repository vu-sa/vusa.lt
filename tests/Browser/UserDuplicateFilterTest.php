<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('filters duplicate members and keeps trash reachable across desktop and phone layouts', function (): void {
    $tenant = Tenant::query()->first();
    $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $first = makeUser($tenant);
    $second = makeUser($tenant);
    $unrelated = makeUser($tenant);
    $deleted = makeUser($tenant);
    $first->update(['name' => 'Jonas Jonaitis']);
    $second->update(['name' => 'Jonaitis Jonas']);
    $unrelated->update(['name' => 'Petras Petraitis']);
    $deleted->update(['name' => 'Ištrintas Narys']);
    $deleted->delete();

    $page = loginAsAdmin($admin);
    $page->navigate('/mano/users');
    waitForInertiaRender($page, '[data-filter-id=duplicates]');

    $page->page()->locator('[data-filter-id=duplicates]')->press('Enter');
    $page->assertDontSee('Petras Petraitis')->assertSee('Jonas Jonaitis')->assertSee('Jonaitis Jonas');
    expect($page->script('new URLSearchParams(location.search).get("duplicates")'))->toBe('possible');

    $page->click('[data-collection-open][href$="/users/'.$first->id.'"]');
    $page->assertPathIs('/mano/users/'.$first->id);
    $page->script('history.back()');
    $page->assertPathIs('/mano/users')->assertVisible('[data-filter-id=duplicates][aria-pressed=true]');
    $page->assertSee('Jonas Jonaitis')->assertSee('Jonaitis Jonas')->assertDontSee('Petras Petraitis');
    $page->script('history.forward()');
    $page->assertPathIs('/mano/users/'.$first->id);
    $page->script('history.back()');
    $page->assertPathIs('/mano/users')->assertVisible('[data-filter-id=duplicates][aria-pressed=true]');
    $page->assertSee('Jonas Jonaitis')->assertSee('Jonaitis Jonas')->assertDontSee('Petras Petraitis');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 1000);
        if ($width < 768) {
            $page->click('[data-slot=collection-filters-toggle]');
        }

        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            $page->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: "member-duplicates-{$width}-{$theme}");
        }

        if ($width < 768) {
            $page->page()->locator('[data-filter-id=duplicates]')->press('Escape');
        }
    }

    $page->navigate('/mano/users?duplicates=possible');
    waitForInertiaRender($page, '[data-slot=collection-page]');
    $page->click('[data-slot=collection-filters-toggle]');
    $page->click('button:has-text("Ištrinti"):has(svg)');
    $page->assertSee('Ištrintas Narys')->assertDontSee('Jonas Jonaitis')->assertNoJavaScriptErrors();
    expect($page->script('document.querySelector("[data-filter-id=duplicates]")'))->toBeNull();
});
