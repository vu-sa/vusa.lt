<?php

use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('selects a tenant institution in the same sheet and preserves it after a browser save', function (): void {
    usesTypesenseInBrowser();
    $tenant = Tenant::factory()->create([
        'fullname' => 'Picker test tenant', 'shortname' => 'Picker tenant', 'type' => TenantType::Padalinys,
    ]);
    $institution = Institution::factory()->for($tenant)->create([
        'name' => ['lt' => 'Picker test institution', 'en' => 'Picker test institution'],
        'is_active' => true,
    ]);
    $page = loginAsAdmin(makeAdminUser($tenant), '/mano/tenants', 390, 900);
    $page->script('document.head.insertAdjacentHTML("beforeend", "<style>*,*::before,*::after{transition:none!important;animation:none!important}</style>")');
    $page->click('[data-collection-open]:has-text("Picker test tenant")');
    $page->assertVisible('#tenant-primary-institution');

    $page->click('#tenant-primary-institution');
    $page->assertSee('Picker test institution');
    $page->type('[data-slot=sheet-form] input[type=text]', 'zzzz-no-institution-matches');
    $page->assertSee('Šio padalinio institucijų nerasta.');
    $page->type('[data-slot=sheet-form] input[type=text]', 'Picker test institution');
    $page->assertSee('Picker test institution');
    $page->click('button[aria-label="Picker test institution"]');
    $page->click('[data-testid=tenant-picker-confirm]');
    $page->assertSeeIn('#tenant-primary-institution', 'Picker test institution');
    $page->click('[data-slot=sheet-form] button[type=submit]');
    $page->assertNotPresent('[data-slot=sheet-form]');
    $page->assertSee('Picker test institution');
    expect($tenant->fresh()->primary_institution_id)->toBe($institution->id);
    $page->assertNoJavaScriptErrors();
});
