<?php

use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens ordering from a quick link menu and saves only its language group', function (): void {
    $tenant = Tenant::query()->first();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $lithuanian = QuickLink::factory()->for($tenant)->create(['text' => 'Lietuviška nuoroda', 'lang' => 'lt', 'order' => 1]);
    $first = QuickLink::factory()->for($tenant)->create(['text' => 'English first', 'lang' => 'en', 'order' => 1]);
    $second = QuickLink::factory()->for($tenant)->create(['text' => 'English second', 'lang' => 'en', 'order' => 2]);

    $page = loginAsAdmin($user, '/mano/quickLinks', 1440, 900);
    $page->assertSee('Lietuviška nuoroda')->assertSee('English first');

    $page->click('tr:has-text("English first") [data-testid=record-overflow-trigger]')
        ->click('[data-action=reorder]')
        ->assertVisible('#reorder-tenant')
        ->assertVisible('#reorder-language');
    expect($page->script('document.querySelector("#reorder-tenant").value'))->toBe((string) $tenant->id);
    expect($page->script('document.querySelector("#reorder-language").value'))->toBe('en');
    $page->assertSeeIn('[data-testid=reorder-list]', 'English first')
        ->assertDontSeeIn('[data-testid=reorder-list]', 'Lietuviška nuoroda');

    $page->select('#reorder-language', 'lt')->assertSeeIn('[data-testid=reorder-list]', 'Lietuviška nuoroda');
    $page->select('#reorder-language', 'en')->assertSeeIn('[data-testid=reorder-list]', 'English second');
    $page->click('[data-testid=reorder-item]:nth-child(2) [data-testid=move-up]')
        ->click('button:has-text("Išsaugoti tvarką")')
        ->assertMissing('#reorder-language')
        ->assertNoJavaScriptErrors();

    expect($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(1)
        ->and($lithuanian->fresh()->order)->toBe(1);
});
