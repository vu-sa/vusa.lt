<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

it('explains closure before saving and preserves the ended assignment after the browser save', function (): void {
    $tenant = Tenant::main();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => ['lt' => 'Veiklą baigianti institucija', 'en' => 'Closing institution'],
        'short_name' => ['lt' => 'VBI', 'en' => 'CI'],
        'description' => [
            'lt' => '<p>Institucija baigia veiklą. Ankstesni narių pareigų laikotarpiai išsaugomi.</p>',
            'en' => '<p>The institution is closing. Members’ previous assignment periods are preserved.</p>',
        ],
        'is_active' => true,
    ]);
    $duty = Duty::factory()->for($institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => today()->subYear()]);
    $page = loginAsAdmin($actor, "/mano/institutions/{$institution->id}/edit", 1440, 900);
    waitForInertiaRender($page, '[data-testid=institution-active-false]');
    $page->click('[data-testid=institution-active-false]');
    $page->assertVisible('[data-testid=institution-deactivation-warning]');

    $page->script('document.querySelector("[data-testid=form-page-aside] [data-slot=form-panel]").scrollIntoView({ block: "center" })');
    docsScreenshot($page, 'institution-deactivation');
    docsScreenshot($page, 'v3-institution-deactivation', highlights: ['[data-testid=institution-deactivation-warning]']);

    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-testid=form-page-bar] [data-save-state=saved]');
    $page->assertNotPresent('[data-testid=institution-deactivation-warning]');
    expect($institution->fresh()->is_active)->toBe(0)
        ->and($term->fresh()->end_date->toDateString())->toBe(today()->subDay()->toDateString())
        ->and($term->fresh()->ended_by_institution_deactivation)->toBeTrue();
    $page->assertNoJavaScriptErrors();
});

it('applies list status changes and retains inactive institution tags across navigation', function (): void {
    usesTypesenseInBrowser();
    $tenant = Tenant::main();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->for($tenant)->create(['name' => ['lt' => 'Būsenos patikra', 'en' => 'Activity review']]);
    $duty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Būsenos pareigybė', 'en' => 'Status duty']]);
    $page = loginAsAdmin($actor, '/mano/institutions?view=preview', 1440, 1000);
    waitForInertiaRender($page, '[data-slot=collection-status-menu]');

    $page->click('[data-slot=collection-rows] li:has-text("Būsenos patikra") [data-collection-open]');
    $page->click('[data-slot=collection-preview] [data-slot=collection-status-menu]');
    $page->click('[role=menuitemradio]:has-text("Nebeveikianti")');
    $page->assertVisible('[data-slot=confirm-dialog-action]');
    $page->click('[data-slot=confirm-dialog-action]');
    waitForInertiaRender($page, '[data-slot=collection-preview] [data-slot=collection-status-menu] [data-status-role=neutral]');
    expect($institution->fresh()->is_active)->toBe(0);

    navigateTo($page, '/mano/duties?view=preview');
    waitForInertiaRender($page, '[data-slot=collection-preview] [data-slot=collection-status-menu]');
    // The preview opens the first row; the actor's own (randomly named) duty can sort before this one.
    $page->click('[data-slot=collection-rows] li:has-text("Būsenos pareigybė") [data-collection-open]');
    $page->assertSee('Nebeveikianti institucija');
    $page->click('[data-slot=collection-preview] [data-slot=collection-status-menu]');
    $page->click('[role=menuitemradio]:has-text("Nebeveikianti")');
    waitForInertiaRender($page, '[data-slot=collection-preview] [data-slot=collection-status-menu] [data-status-role=neutral]');
    expect($duty->fresh()->is_active)->toBeFalse();
    navigateTo($page, '/mano/institutions?view=table');
    waitForInertiaRender($page, '[data-slot=collection-table]');
    navigateTo($page, '/mano/duties?view=table');
    waitForInertiaRender($page, '[data-slot=collection-table]');
    $page->assertNoJavaScriptErrors();
});

it('opens a restored untitled institution and keeps it identifiable in the live list', function (): void {
    usesTypesenseInBrowser();
    $tenant = Tenant::main();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->for($tenant)->create();
    $institution->delete();
    DB::table('institutions')->where('id', $institution->id)->update(['name' => null, 'short_name' => null]);
    $institution = $institution->fresh();
    $page = loginAsAdmin($actor, '/mano/institutions?showDeleted=true', 390, 900);
    waitForInertiaRender($page, '[data-slot=institution-collection-row]');
    $page->assertSee('Be pavadinimo · '.$institution->alias);
    $page->click('[data-slot=institution-collection-row]:has-text("'.$institution->alias.'") button[aria-label="Daugiau veiksmų"]:visible');
    $page->click('[role=dialog] button:has-text("Atkurti ir atidaryti")');
    waitForInertiaRender($page, '[data-slot=record-title]');
    $page->assertPathIs('/mano/institutions/'.$institution->id);
    $page->assertSee('Be pavadinimo · '.$institution->alias);
    expect($institution->fresh()->getRawOriginal('name'))->toBeNull();
    navigateTo($page, '/mano/institutions');
    waitForInertiaRender($page, '[data-slot=institution-collection-row]:has-text("'.$institution->alias.'")');
    $page->assertNoJavaScriptErrors();
});
