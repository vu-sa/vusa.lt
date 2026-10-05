<?php

use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Real meetings, tasks and coordinators: empty states would hide both layout and data bugs.
    $this->seed(DocsSeeder::class);
});

/** No `$t()` call fell through to its raw key; lower-cased since CSS uppercases headings. */
function expectNoRawVisakKeys($page): void
{
    // admin.ts mounts before the translation JSON arrives, so a slow runner can read keys that are about to swap out.
    $script = '/\b(visak|tasks|atstovavimas)\.[a-z_]+\.[a-z_]/.test(document.body.innerText.toLowerCase())';
    $deadline = microtime(true) + 5;

    while ($page->script($script) && microtime(true) < $deadline) {
        usleep(100_000);
    }

    expect($page->script($script))->toBeFalse();
}

/** The page fits the viewport at a phone and a desktop width. */
function expectVisakPageFits($page, string $slot): void
{
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
            ->and($page->script("document.querySelector('[data-slot={$slot}]').getBoundingClientRect().width <= window.innerWidth"))->toBeTrue();
    }
}

it('shows a representative their institutions and upcoming meetings on the ViSAK overview', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/dashboard/atstovavimas');
    waitForInertiaRender($page, '[data-slot=atstovavimas-primary-section]');

    $page->assertSee('Chemijos ir geomokslų fakulteto taryba');
    expectNoRawVisakKeys($page);

    expectVisakPageFits($page, 'atstovavimas-primary-section');

    $page->resize(1440, 1200);
    docsScreenshot($page, 'visak-overview');

    $page->assertNoJavaScriptErrors();
});

it('shows a padalinys coordinator the statistics of their padalinys', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::COORDINATOR_EMAIL));
    $page->navigate('/mano/dashboard/atstovavimas/padaliniai');
    waitForInertiaRender($page, '[data-slot=atstovavimas-tenant-primary-section]');

    expectNoRawVisakKeys($page);

    expectVisakPageFits($page, 'atstovavimas-tenant-primary-section');

    $page->resize(1440, 1200);
    docsScreenshot($page, 'visak-padaliniai');
    docsScreenshot($page, 'v3-ask-representatives', highlights: ['[data-slot=institutions-needing-attention-ask]']);

    $page->assertNoJavaScriptErrors();
});

it('lists the padaliniai tasks for the central coordinator', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::CENTRAL_COORDINATOR_EMAIL));
    $page->navigate('/mano/tasks/summary');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    // The representative's agenda task, raised by the seeded meeting through its subscriber.
    $page->assertSee('Užpildyti darbotvarkės');
    expectNoRawVisakKeys($page);

    expectVisakPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'tasks-summary');

    $page->assertNoJavaScriptErrors();
});

it('lists a representative\'s own tasks', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/tasks');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    expectNoRawVisakKeys($page);
    expectVisakPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'tasks-index');

    $page->assertNoJavaScriptErrors();
});
