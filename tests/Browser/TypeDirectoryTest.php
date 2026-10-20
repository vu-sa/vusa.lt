<?php

use App\Enums\ResponsibilityPurpose;
use App\Models\DutyType;
use App\Models\ResponsibilityGrant;
use App\Models\Tenant;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens concrete type collections and their forms without browser errors', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->navigate('/mano/types');
    waitForInertiaRender($page, '[data-slot=navigation-tiles]');
    $page->assertPresent('[data-tile=instituciju_tipai]')->assertPresent('[data-tile=pareigybiu_tipai]');
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }
    $page->click('[data-tile=instituciju_tipai]');
    waitForInertiaRender($page, '[data-slot=collection-table]');
    $page->assertNoJavaScriptErrors();
    $page->click('[data-slot=collection-title-band] a[href$="/institutionTypes/create"]');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertPathIs('/mano/institutionTypes/create')->assertNoJavaScriptErrors();
    $page->navigate('/mano/dutyTypes');
    waitForInertiaRender($page, '[data-slot=collection-table]');
    $page->click('[data-slot=collection-title-band] a[href$="/dutyTypes/create"]');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertPathIs('/mano/dutyTypes/create')->assertDontSee('rich-content.')->assertNoJavaScriptErrors();
    $page->resize(390, 844);
    $page->script('document.documentElement.classList.add("dark")');
    expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    $page->navigate('/mano/institutionTypes/create');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertNoJavaScriptErrors();
});

it('opens scoped responsibility grants from a type across widths and themes', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $type = DutyType::factory()->create(['title' => ['lt' => 'Koordinatorių pareigybės', 'en' => 'Coordinator duties']]);
    $coordination = RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);
    ResponsibilityGrant::factory()->forDutyType($type)->of($coordination)->forTenant($tenant)->create();
    ResponsibilityGrant::factory()->forDutyType($type)->of($coordination)->create();
    $page = loginAsAdmin(makeAdminUser($tenant));
    $page->navigate('/mano/dutyTypes/'.$type->id);
    waitForInertiaRender($page);

    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);
            if ($width >= 768) {
                $page->click('button[role="tab"]:has-text("Atsakomybės")');
            }
            $page->assertVisible('[data-testid="type-grant-needs-scope"]');
            expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
            $page->script('document.querySelector("[data-testid=type-responsibility-grants]").scrollIntoView({block: "center"})');
            $theme = $dark ? 'dark' : 'light';
            $page->screenshot(fullPage: false, filename: "type-grants-{$width}-{$theme}");
        }
    }

    $page->click('[data-testid="type-grant-row"]:first-child a');
    waitForInertiaRender($page, '[data-slot=permission-matrix]');
    $page->assertPathIs('/mano/responsibilities/'.$coordination->id);
    $page->click('button[role="tab"]:has-text("Kam suteikta")');
    $page->assertVisible('[data-testid="grant-needs-scope"]')->assertNoJavaScriptErrors();
});
