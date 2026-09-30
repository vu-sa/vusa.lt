<?php

use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the duty responsibilities readable across admin widths and themes', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $tenant = Tenant::query()->firstOrFail();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    DutyResponsibility::factory()->for($duty)->forTenant($tenant)->create();

    $page = loginAsAdmin(makeAdminUser($tenant));
    $page->navigate('/mano/duties/'.$duty->id);
    waitForInertiaRender($page);
    $page->assertPresent('[data-slot="duty-responsibilities"]');

    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);

            if ($width === 820) {
                $page->click('button[role="tab"]:has-text("Atsakomybės")');
            }

            $bounds = $page->script(<<<'JS'
                (() => {
                  const section = document.querySelector('[data-slot="duty-responsibilities"]');
                  const rect = section.getBoundingClientRect();
                  return {
                    left: rect.left,
                    right: rect.right,
                    documentWidth: document.documentElement.scrollWidth,
                  };
                })()
            JS);

            expect($bounds['left'])->toBeGreaterThanOrEqual(-1)
                ->and($bounds['right'])->toBeLessThanOrEqual($width + 1)
                ->and($bounds['documentWidth'])->toBeLessThanOrEqual($width + 1);

        }
    }

    $page->resize(1440, 900);
    docsScreenshot($page, 'duty-responsibilities', selector: '[data-slot="duty-responsibilities"]');

    $page->assertNoJavaScriptErrors();
});
