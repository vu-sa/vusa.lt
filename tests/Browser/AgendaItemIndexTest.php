<?php

use App\Enums\AgendaItemType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('offers a super admin every action on an item of their own institution, on rows, table and preview', function (): void {
    usesTypesenseInBrowser();

    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $meeting = Meeting::factory()->create();
    $user = makeAdminUser($tenant);
    $meeting->institutions()->attach($user->duties()->first()->institution);
    $item = AgendaItem::factory()->for($meeting)->create(['title' => ['lt' => 'Stipendijų tvarka'], 'type' => AgendaItemType::Informational]);
    $item->searchable();
    $meeting->searchable();

    $page = loginAsAdmin($user, '/mano/agendaItems?tenant_shortnames='.$tenant->shortname);
    $page->assertSee('Stipendijų tvarka')->assertSee('Aptarimas');

    $openMenu = fn () => $page->page()->locator('[data-slot="collection-row-actions"] button[aria-label="Daugiau veiksmų"]:visible')->first()->click();
    $seesAllActions = fn () => $page->assertVisible('[data-slot=dropdown-menu-content] [role=menuitem]:has-text("Redaguoti")')
        ->assertVisible('[data-slot=dropdown-menu-content] [role=menuitem]:has-text("Ištrinti")');

    $openMenu();
    $seesAllActions();

    foreach (['table', 'preview&item='.$item->id] as $view) {
        $page->navigate('/mano/agendaItems?tenant_shortnames='.$tenant->shortname.'&view='.$view);
        $page->assertSee('Aptarimas');
        $openMenu();
        $seesAllActions();
    }

    $page->assertNoJavaScriptErrors();
});
