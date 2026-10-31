<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('refreshes trash rows after permanent deletion without reloading the page', function (string $collection): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create([
        'name' => ['lt' => 'Šiukšlinės institucija', 'en' => 'Trash institution'],
    ]);
    $record = $collection === 'institutions' ? $institution : Duty::factory()->for($institution)->create([
        'name' => ['lt' => 'Šiukšlinės pareigybė', 'en' => 'Trash duty'],
    ]);
    $name = $record->getTranslation('name', 'lt');
    $record->delete();

    $page = loginAsAdmin(makeAdminUser($tenant), '/mano/'.$collection.'?showDeleted=true');
    $page->assertSee($name);
    $page->script('window.trashRefreshMarker = "preserved"');
    $page->page()->locator('[data-slot="collection-row-actions"] button[aria-label="Daugiau veiksmų"]:visible')->first()->click();
    $page->click('[data-slot=dropdown-menu-content] [role=menuitem]:has-text("Ištrinti visam laikui")');
    $page->click('[data-slot="confirm-dialog-action"]');
    $page->assertDontSee($name)->assertNoJavaScriptErrors();

    expect($page->script('window.trashRefreshMarker'))->toBe('preserved')
        ->and($record->newQuery()->withTrashed()->find($record->getKey()))->toBeNull();
})->with(['institutions', 'duties']);
