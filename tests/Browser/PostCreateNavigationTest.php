<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens the created duty after saving on desktop and phone', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->for($tenant)->create();
    $createPath = '/mano/duties/create?institution_id='.$institution->id;
    $page = loginAsAdmin($actor, $createPath, 1440, 900);

    foreach ([1440, 390] as $width) {
        $name = "Nauja pareigybė {$width}";
        if ($width !== 1440) {
            $page->resize($width, 900);
            navigateTo($page, $createPath);
        }
        waitForInertiaRender($page, '#duty-name');
        $page->fill('#duty-name', $name);
        $page->click($width === 390 ? '[data-testid=form-page-mobile-save] button' : '[data-testid=form-page-save]');

        $page->assertPresent('[data-slot=record-title]');
        $created = Duty::where('name->lt', $name)->sole();
        $page->assertPathIs('/mano/duties/'.$created->id)->assertNoJavaScriptErrors();
    }
});
