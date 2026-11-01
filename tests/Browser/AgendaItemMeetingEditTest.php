<?php

use App\Enums\AgendaItemType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('edits an agenda item in a sheet from the meeting list', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $user = makeAdminUser($tenant);
    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach($user->duties()->first()->institution);
    $item = AgendaItem::factory()->for($meeting)->create(['title' => ['lt' => 'Senas klausimas'], 'type' => AgendaItemType::Informational]);

    $page = loginAsAdmin($user, '/mano/meetings/'.$meeting->id);
    $page->click('[data-testid="agenda-item-edit"]');
    $page->assertVisible('#agenda-item-title');
    $page->fill('#agenda-item-title', 'Naujas klausimas');
    $page->click('[data-slot="sheet-form-submit"], button[type="submit"]');
    $page->assertSee('Naujas klausimas')->assertPathIs('/mano/meetings/'.$meeting->id)->assertNoJavaScriptErrors();

    expect($item->fresh()->getTranslation('title', 'lt'))->toBe('Naujas klausimas');
});
