<?php

use App\Jobs\SyncManagedMailingLists;
use App\Models\MailingList;
use App\Models\MailingListMessage;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

it('saves a mailing list source on a phone and keeps its record readable across widths and themes', function (): void {
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);
    Http::preventStrayRequests();
    Http::fake(['https://konf.test/3.1/lists/*/roster/member*' => Http::response(['entries' => [], 'total_size' => 0])]);
    $tenant = Tenant::main();
    $editor = makeTenantUserWithRole('Studentų atstovų koordinatorius', $tenant);
    $list = MailingList::factory()->for($tenant)->create([
        'address' => 'taryba@konf.vusa.lt',
        'display_name' => 'VU SA Taryba',
    ]);
    $list->exclusions()->create(['email' => 'neitraukti@vusa.lt']);
    $page = loginAsAdmin($editor, '/mano/mailingLists/'.$list->id, 390, 844);
    waitForInertiaRender($page, '[data-slot=mailing-list-sources]');
    $page->click('[data-testid=source-add]');
    $page->select('#source_type', 'email');
    $page->type('#source_email', 'patarejas@vusa.lt');
    $page->type('#source_note', 'Tarybos patarėjas');
    $page->click('[data-slot=sheet-form] button[type=submit]');
    $page->assertSee('patarejas@vusa.lt');
    expect($list->sources()->sole()->email)->toBe('patarejas@vusa.lt');

    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);
            $page->script('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
            expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
        }
    }

    $page->resize(1440, 1000)->navigate('/mano/mailingLists/'.$list->id);
    waitForInertiaRender($page, '[data-slot=record-title]');
    $page->click('button[role=tab]:has-text("Šaltiniai")');
    $page->script('document.documentElement.classList.remove("dark")');
    docsScreenshot($page, 'mailing-list-sources');
    docsScreenshot($page, 'v3-mailing-lists', highlights: [
        '[data-slot=mailing-list-sources] [data-slot=overview-section]:first-child',
        '[data-slot=mailing-list-sources] [data-slot=overview-section]:nth-child(2)',
    ]);
    $page->assertNoJavaScriptErrors();
});

it('loads older archived messages through the built Inertia pagination and opens the collection', function (): void {
    $tenant = Tenant::main();
    $list = MailingList::factory()->for($tenant)->create(['archive_backfilled_at' => now()]);
    MailingListMessage::factory()->count(51)->for($list)->sequence(fn ($sequence) => [
        'subject' => 'Laiškas '.$sequence->index,
        'sent_at' => '2026-10-16 12:00:00',
    ])->create();
    $page = loginAsAdmin(makeTenantUserWithRole('Studentų atstovų koordinatorius', $tenant), '/mano/mailingLists/'.$list->id, 1440, 900);
    waitForInertiaRender($page, '[data-slot=record-title]');
    $page->click('button[role=tab]:has-text("Archyvas")');
    $page->assertSee('Laiškas 50')->assertDontSee('Laiškas 0');
    $page->click('button:has-text("Rodyti daugiau")');
    $page->assertSee('Laiškas 0');

    $page->navigate('/mano/mailingLists');
    waitForInertiaRender($page, '[data-slot=collection-page]');
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);
        $page->script('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
        expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }
    $page->assertNoJavaScriptErrors();
});
