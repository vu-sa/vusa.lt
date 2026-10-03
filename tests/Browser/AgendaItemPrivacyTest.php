<?php

use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Settings\MeetingSettings;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('privacy can be saved from the responsive editor and public pages show only the placeholder', function (): void {
    $this->seed(DocsSeeder::class);
    $item = AgendaItem::query()->where('title->lt', DocsSeeder::UNFINISHED_AGENDA_ITEM)->firstOrFail();
    $user = User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL);
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => $item->meeting->institutions->flatMap(fn ($institution) => $institution->types->pluck('id'))->all(),
    ])->save();
    $page = loginAsAdmin($user);
    $page->navigate("/mano/agendaItems/{$item->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');
    $page->click('[data-testid=agenda-item-edit-visibility]');
    $page->resize(390, 900);
    $page->click('[data-testid=agenda-item-privacy-details] summary');
    $page->click('#agenda-item-private');
    $page->fill('#agenda-item-public-title', 'Darbo klausimas');

    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);
        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
    }
    $page->script('document.documentElement.classList.add("dark")');
    $page->click('[data-slot=sheet-form] button[type=submit]');
    $page->assertNotPresent('[data-slot=sheet-form]');
    expect($item->fresh()->is_private)->toBeTrue()
        ->and($item->fresh()->getTranslation('public_title', 'lt'))->toBe('Darbo klausimas');
    $page->assertNoJavaScriptErrors();

    $public = visitPublicSubdomain('www', '/lt/posedziai/'.$item->meeting_id);
    $public->assertSee('Darbo klausimas')->assertSee('Tik viduje')
        ->assertDontSee(DocsSeeder::UNFINISHED_AGENDA_ITEM)
        ->assertNoJavaScriptErrors();
});
