<?php

use App\Models\Institution;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // A meeting with one recorded and one unfinished vote: empty records would hide layout and data bugs.
    $this->seed(DocsSeeder::class);

    $this->representative = User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL);
    $this->council = Institution::query()->where('name->lt', DocsSeeder::COUNCIL_INSTITUTION)->firstOrFail();
    $this->unfinishedItem = AgendaItem::query()->where('title->lt', DocsSeeder::UNFINISHED_AGENDA_ITEM)->firstOrFail();
});

/** No `$t()` call fell through to its raw key; lower-cased since CSS uppercases headings. */
function expectNoRawRecordKeys($page): void
{
    // admin.ts mounts before the translation JSON arrives, so a slow runner can read keys that are about to swap out.
    $script = '/\b(institutions|meetings|agenda_items|visak|shell)\.[a-z_]+/.test(document.body.innerText.toLowerCase())';
    $deadline = microtime(true) + 5;

    while ($page->script($script) && microtime(true) < $deadline) {
        usleep(100_000);
    }

    expect($page->script($script))->toBeFalse();
}

/** The record fits the viewport at a phone and a desktop width. */
function expectRecordFits($page): void
{
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
            ->and($page->script("document.querySelector('[data-slot=record-facts]').getBoundingClientRect().width <= window.innerWidth"))->toBeTrue();
    }
}

it('opens a representative\'s institution with its status and meetings', function (): void {
    $page = loginAsAdmin($this->representative);
    $page->navigate("/mano/institutions/{$this->council->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee(DocsSeeder::COUNCIL_INSTITUTION);
    expectNoRawRecordKeys($page);

    expectRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'institution-record');

    $page->assertNoJavaScriptErrors();
});

it('opens a past meeting with its agenda and what is still missing', function (): void {
    $page = loginAsAdmin($this->representative);
    $page->navigate("/mano/meetings/{$this->unfinishedItem->meeting_id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee(DocsSeeder::UNFINISHED_AGENDA_ITEM);
    expectNoRawRecordKeys($page);

    expectRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'meeting-record');

    $page->assertNoJavaScriptErrors();
});

it('opens an agenda item with its vote to finish', function (): void {
    $page = loginAsAdmin($this->representative);
    $page->navigate("/mano/agendaItems/{$this->unfinishedItem->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee(DocsSeeder::UNFINISHED_AGENDA_ITEM);
    expectNoRawRecordKeys($page);

    expectRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'agenda-item-record');

    $page->assertNoJavaScriptErrors();
});
