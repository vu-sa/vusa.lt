<?php

use App\Models\InstitutionStatusSnapshot;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RepStatisticsScope;
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
    $script = '/\b(visak|tasks|atstovavimas)\.[a-z_]+\.[a-z_]/.test(document.body.innerText.toLowerCase())';
    $page->assertScript($script, false);
}

/** The page fits the viewport at a phone and a desktop width. */
function expectVisakPageFits($page, string $slot): void
{
    foreach (['light', 'dark'] as $theme) {
        $page->script("document.documentElement.classList.toggle('dark', '".$theme."' === 'dark')");
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);
            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
                ->and($page->script("document.querySelector('[data-slot={$slot}]').getBoundingClientRect().width <= window.innerWidth"))->toBeTrue();
        }
    }
    $page->script("document.documentElement.classList.remove('dark')");
}

it('shows a representative their institutions and upcoming meetings on the ViSAK overview', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL), '/mano/dashboard/atstovavimas');
    waitForInertiaRender($page, '[data-slot=atstovavimas-primary-section]');

    $page->assertSee('Chemijos ir geomokslų fakulteto taryba');
    expectNoRawVisakKeys($page);

    expectVisakPageFits($page, 'atstovavimas-primary-section');

    $page->resize(1440, 1200);
    docsScreenshot($page, 'visak-overview');

    $page->assertNoJavaScriptErrors();
});

it('shows a padalinys coordinator the statistics of their padalinys', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $institutions = RepStatisticsScope::institutions([$tenant->id])->pluck('id');
    foreach (range(1, 14) as $daysAgo) {
        InstitutionStatusSnapshot::factory()->for($tenant)->create([
            'taken_on' => today()->subDays($daysAgo)->toDateString(),
            'all' => $institutions->count(), 'needs_attention' => $institutions->count(), 'overdue' => $institutions->count(),
            'approaching' => 0, 'no_activity' => 0, 'recent_check_in' => 0, 'current' => 0,
            'left_attention' => 1, 'entered_attention' => 1,
            'institution_statuses' => $institutions->mapWithKeys(fn ($id) => [$id => 'overdue'])->all(),
        ]);
    }
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::COORDINATOR_EMAIL), '/mano/dashboard/atstovavimas/padaliniai');
    waitForInertiaRender($page, '[data-slot=atstovavimas-tenant-primary-section]');

    expectNoRawVisakKeys($page);

    expectVisakPageFits($page, 'atstovavimas-tenant-primary-section');

    foreach ([30, 90, 180] as $range) {
        $page->click("[data-testid=trend-range-{$range}]");
        waitForInertiaRender($page, '[data-slot=institution-trend-plot][aria-busy=false]');
        $page->resize(390, 900);
        $page->script('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(() => resolve(true))))');
        expect($page->script("(() => {
            const bars = [...document.querySelectorAll('[data-testid=trend-chart-svg] rect[data-direction]')].filter(bar => bar.dataset.direction === '1').map(bar => bar.getBoundingClientRect());
            return bars.length > 1 && bars.every((bar, index) => index === 0 || bars[index - 1].right < bar.left);
        })()"))->toBeTrue();
    }
    $page->click('[data-testid=trend-range-90]');
    waitForInertiaRender($page, '[data-slot=institution-trend-plot][aria-busy=false]');
    $page->resize(1440, 1200);
    // A pending resize redraw replaces the inspector and dismisses its tooltip; retry both together.
    $page->assertScript(<<<'JS'
        (async () => {
            const inspector = document.querySelector('[data-testid=trend-inspector]');
            if (!inspector) return false;
            inspector.blur();
            inspector.focus({ preventScroll: true });
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            const box = document.querySelector('[data-slot=trend-tooltip]')?.getBoundingClientRect();
            return !!box && box.left >= 0 && box.top >= 0 && box.right <= innerWidth && box.bottom <= innerHeight;
        })()
        JS);
    $page->script("document.querySelector('[data-testid=trend-inspector]').blur()");
    waitForInertiaRender($page, '[data-slot=institutions-needing-attention-ask]');
    docsScreenshot($page, 'visak-padaliniai');
    docsScreenshot($page, 'v3-ask-representatives', highlights: ['[data-slot=institutions-needing-attention-ask]']);

    $page->script("document.querySelector('[data-slot=atstovavimas-rep-metrics]').scrollIntoView()");
    waitForInertiaRender($page, '[data-testid=tenant-rep-metrics-table]');
    expectVisakPageFits($page, 'atstovavimas-rep-metrics');
    docsScreenshot($page, 'visak-atstovu-rodikliai');

    $page->assertNoJavaScriptErrors();
});

it('lists the padaliniai tasks for the central coordinator', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::CENTRAL_COORDINATOR_EMAIL), '/mano/tasks/summary');
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
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL), '/mano/tasks');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    expectNoRawVisakKeys($page);
    expectVisakPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'tasks-index');

    $page->assertNoJavaScriptErrors();
});
