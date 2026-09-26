<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Real requests in every stage and a filled cart: empty states would hide both layout and data bugs.
    $this->seed(DocsSeeder::class);
});

/** No `$t()` call fell through to its raw key. Rendered text only (not the page-data script), lower-cased since CSS uppercases headings. */
function expectNoRawReservationKeys($page): void
{
    // admin.ts mounts before the translation JSON arrives, so a slow runner can read keys that are about to swap out.
    $script = 'document.body.innerText.toLowerCase().includes("reservations.")';
    $deadline = microtime(true) + 5;

    while ($page->script($script) && microtime(true) < $deadline) {
        usleep(100_000);
    }

    expect($page->script($script))->toBeFalse();
}

/** The page fits the viewport at a phone and a desktop width. */
function expectReservationPageFits($page, string $slot): void
{
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
            ->and($page->script("document.querySelector('[data-slot={$slot}]').getBoundingClientRect().width <= window.innerWidth"))->toBeTrue();
    }
}

it('shows a resource manager the requests waiting for them on the overview', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::RESOURCE_MANAGER_EMAIL));
    $page->navigate('/mano/dashboard/reservations');
    waitForInertiaRender($page, '[data-slot=reservations-overview-columns]');

    $page->assertSee('Pirmakursių stovyklos įranga');
    expectNoRawReservationKeys($page);

    expectReservationPageFits($page, 'reservations-overview-columns');

    $page->resize(1440, 900);
    docsScreenshot($page, 'reservations-overview');

    $page->assertNoJavaScriptErrors();
});

it('opens the checkout with the representative\'s saved cart', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/reservations/create');
    waitForInertiaRender($page, '[data-slot=form-page]');

    $page->assertSee('Renginių palapinė 3×3 m')
        ->assertSee('VU SA vėliava');
    expectNoRawReservationKeys($page);

    expectReservationPageFits($page, 'form-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'reservation-form');

    $page->assertNoJavaScriptErrors();
});

it('offers the manager the next step for each item on the reservation page', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::RESOURCE_MANAGER_EMAIL));
    $reservation = Reservation::query()->where('name', DocsSeeder::MIXED_RESERVATION_NAME)->firstOrFail();
    $page->navigate("/mano/reservations/{$reservation->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    // Waiting → approve, reserved → hand over, lent → mark returned.
    $page->assertSee('Tvirtinti')
        ->assertSee('Išduoti')
        ->assertSee('Grąžinti');
    expectNoRawReservationKeys($page);

    $page->resize(1440, 1200);
    docsScreenshot($page, 'reservation-decisions');

    $page->assertNoJavaScriptErrors();
});

it('lists every padalinys\' resources as cards with what is free', function (): void {
    // The list reads Typesense from the browser, which tests leave off unless asked.
    usesTypesenseInBrowser();
    Resource::query()->get()->searchable();

    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/resources');
    waitForInertiaRender($page, '[data-slot=resource-collection-card]');

    $page->assertSee('Garso kolonėlė JBL PartyBox')
        ->assertSee('Projektorius Epson');
    expectNoRawReservationKeys($page);
    expectReservationPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'resources-index');

    $page->assertNoJavaScriptErrors();
});
