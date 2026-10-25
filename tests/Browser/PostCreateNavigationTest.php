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
    $page = loginAsAdmin($actor);

    foreach ([1440, 390] as $width) {
        $name = "Nauja pareigybė {$width}";
        $page->resize($width, 900);
        navigateTo($page, '/mano/duties/create?institution_id='.$institution->id);
        waitForInertiaRender($page, '#duty-name');
        $page->fill('#duty-name', $name);
        $page->click($width === 390 ? '[data-testid=form-page-mobile-save] button' : '[data-testid=form-page-save]');

        $page->assertPresent('[data-slot=record-title]');
        $created = Duty::where('name->lt', $name)->sole();
        $page->assertPathIs('/mano/duties/'.$created->id)->assertNoJavaScriptErrors();
        $page->screenshot(fullPage: false, filename: 'post-create-duty-'.$width);
    }
});
