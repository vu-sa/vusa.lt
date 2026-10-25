<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('saves and clears a duty reference through the built form on desktop and phone', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $path = '/mano/duties/'.$duty->id;
    $page = loginAsAdmin($actor, $path.'/edit', 1440, 900);

    $page->fill('#places_to_occupy', '38');
    $page->click('[data-testid=form-page-save]')->assertSee('Visi pakeitimai išsaugoti');
    expect($duty->fresh()->places_to_occupy)->toBe(38);

    navigateTo($page, $path);
    $page->assertPresent('[data-slot=record-facts]')->assertSee('Vietų skaičius');
    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);
            $page->screenshot(fullPage: false, filename: 'duty-reference-'.$width.($dark ? '-dark' : '-light'));
        }
    }

    $page->resize(390, 900);
    navigateTo($page, $path.'/edit');
    $page->assertPresent('#places_to_occupy')->fill('#places_to_occupy', '');
    $page->click('[data-testid=form-page-mobile-save] button')->assertSee('Visi pakeitimai išsaugoti');
    expect($duty->fresh()->places_to_occupy)->toBeNull();
    $page->screenshot(fullPage: false, filename: 'duty-reference-cleared-phone');

    navigateTo($page, $path);
    $page->assertPresent('[data-slot=record-facts]')->assertDontSee('Vietų skaičius')->assertNoJavaScriptErrors();
});
