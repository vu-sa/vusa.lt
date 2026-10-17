<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    $page = loginAsAdmin($actor);
    $page->navigate("/mano/institutions/{$institution->id}/edit");
    waitForInertiaRender($page, '[data-slot=form-toggle-row]');
    $page->click('[data-slot=form-toggle-row] [role=switch]');
    $page->assertVisible('[data-testid=institution-deactivation-warning]');

    foreach (['lt', 'en'] as $locale) {
        if ($locale === 'en') {
            $page->navigate("/mano/institutions/{$institution->id}/edit?lang=en");
            waitForInertiaRender($page, '[data-slot=form-toggle-row]');
            $page->click('[data-slot=form-toggle-row] [role=switch]');
        }
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            foreach ([390, 1440] as $width) {
                $page->resize($width, 900);
                $page->screenshotElement('[data-testid=institution-deactivation-warning]', filename: "institution-closure-{$locale}-{$theme}-{$width}");
            }
        }
        if ($locale === 'lt') {
            $page->script('document.documentElement.classList.remove("dark")');
            $page->script('document.querySelector("[data-testid=form-page-aside] [data-slot=form-panel]").scrollIntoView({ block: "center" })');
            docsScreenshot($page, 'institution-deactivation');
            docsScreenshot($page, 'v3-institution-deactivation', highlights: ['[data-testid=institution-deactivation-warning]']);
        }
    }

    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-testid=form-page-bar] [data-save-state=saved]');
    $page->assertNotPresent('[data-testid=institution-deactivation-warning]');
    expect($institution->fresh()->is_active)->toBe(0)
        ->and($term->fresh()->end_date->toDateString())->toBe(today()->subDay()->toDateString())
        ->and($term->fresh()->ended_by_institution_deactivation)->toBeTrue();
    $page->assertNoJavaScriptErrors();
});
