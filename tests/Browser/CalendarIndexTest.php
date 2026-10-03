<?php

use App\Models\Calendar;
use App\Models\EventType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('edits event type and status in the table and offers meeting creation in the form', function (): void {
    $tenant = Tenant::query()->first();
    $user = makeAdminUser($tenant);
    $type = EventType::factory()->create(['name' => ['lt' => 'Seminaras', 'en' => 'Seminar']]);
    $event = Calendar::factory()->for($tenant)->create([
        'title' => ['lt' => 'Renginio tipas', 'en' => 'Event type'],
        'event_type_id' => null,
        'is_draft' => true,
    ]);

    $page = loginAsAdmin($user);
    $page->navigate('/mano/calendar');
    waitForInertiaRender($page, '[data-slot=collection-table]');

    $page->click('button[aria-label="Renginio tipas"]');
    $page->click('[role=option]:has-text("Seminaras")');
    $page->assertSee('Seminaras');
    $this->assertDatabaseHas('calendar', ['id' => $event->id, 'event_type_id' => $type->id]);

    $page->click('[data-slot=collection-status-menu]');
    $page->click('[role=menuitemradio]:has-text("Paskelbta")');
    $page->assertPresent('[data-slot=collection-status-menu][aria-label="Keisti būseną: Paskelbta"]');
    $this->assertDatabaseHas('calendar', ['id' => $event->id, 'is_draft' => 0]);

    $page->navigate('/mano/calendar/'.$event->id.'/edit');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertSee('Sukurti posėdį iš renginio');
    $page->script('window.__pestBrowser.jsErrors = window.__pestBrowser.jsErrors.filter(error => !error.message.startsWith("ResizeObserver loop completed"))');
    $page->assertNoJavaScriptErrors();
});
