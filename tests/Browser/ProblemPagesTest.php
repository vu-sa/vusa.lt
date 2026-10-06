<?php

use App\Models\Problem;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Problems in every status, one raised in a council agenda item: empty pages would hide layout bugs.
    $this->seed(DocsSeeder::class);

    $this->representative = User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL);
});

/** No `$t()` call fell through to its raw key; lower-cased since CSS uppercases headings. */
function expectNoRawProblemKeys($page): void
{
    // admin.ts mounts before the translation JSON arrives, so a slow runner can read keys that are about to swap out.
    $script = '/\b(shell|problems|entities)\.[a-z_]+\.[a-z_]/.test(document.body.innerText.toLowerCase())';
    $deadline = microtime(true) + 5;

    while ($page->script($script) && microtime(true) < $deadline) {
        usleep(100_000);
    }

    expect($page->script($script))->toBeFalse();
}

/** The page fits the viewport at a phone and a desktop width. */
function expectProblemPageFits($page, string $slot): void
{
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);
        $page->script('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
            ->and($page->script("document.querySelector('[data-slot={$slot}]').getBoundingClientRect().width <= window.innerWidth"))->toBeTrue();
    }
}

it('lists every padalinys\' problems with their status', function (): void {
    $page = loginAsAdmin($this->representative);
    $page->navigate('/mano/problems');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    $page->assertSee(DocsSeeder::OPEN_PROBLEM);
    expectNoRawProblemKeys($page);
    expectProblemPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'problems-index');

    $page->assertNoJavaScriptErrors();
});

it('opens a problem with where it was discussed', function (): void {
    $problem = Problem::query()->where('title->lt', DocsSeeder::OPEN_PROBLEM)->firstOrFail();

    $page = loginAsAdmin($this->representative);
    $page->navigate("/mano/problems/{$problem->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee(DocsSeeder::OPEN_PROBLEM)
        ->assertSee('Svarstyta posėdžiuose');
    expectNoRawProblemKeys($page);
    expectProblemPageFits($page, 'record-facts');

    $page->resize(1440, 1000);
    docsScreenshot($page, 'problem-record');

    $page->click('[role=tab]:has-text("Svarstyta posėdžiuose")');
    docsScreenshot($page, 'v3-problem-meetings', highlights: [
        '[role=tab][aria-selected=true]',
        'section[aria-labelledby=record-section-posedziai]',
    ]);

    $page->assertNoJavaScriptErrors();
});

it('registers a problem through the form', function (): void {
    $page = loginAsAdmin($this->representative);
    // Reached from the list, as a rep would: a hard load mounts the editors before translations arrive.
    $page->resize(1440, 1000)->navigate('/mano/problems');
    waitForInertiaRender($page, '[data-slot=collection-page]');
    expectNoRawProblemKeys($page);
    $page->click('[data-slot=collection-page] a[href$="/mano/problems/create"]');
    waitForInertiaRender($page, '#problem-title');

    $page->type('#problem-title', 'Per mažai vietų skaitykloje sesijos metu');
    $page->click('[data-testid=problem-description-editor] .ProseMirror');
    $page->type('[data-testid=problem-description-editor] .ProseMirror', 'Sesijos metu skaitykloje trūksta vietų.');
    $page->click('#problem-tenant');
    $page->click('[role=option]');

    // Picking the padalinys scrolls the form's own container; the frame starts at the top.
    $page->script('document.querySelectorAll("*").forEach(element => { if (element.scrollTop > 0) element.scrollTop = 0; })');
    docsScreenshot($page, 'problem-form');

    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    expect(Problem::query()->where('title->lt', 'Per mažai vietų skaitykloje sesijos metu')->value('created_by'))
        ->toBe($this->representative->id);

    $page->assertNoJavaScriptErrors();
});

it('lets a central coordinator manage the shared categories', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::CENTRAL_COORDINATOR_EMAIL));
    $page->navigate('/mano/problemCategories');
    waitForInertiaRender($page, '[data-slot=collection-page]');

    $page->assertSee('Komunikacija');
    expectNoRawProblemKeys($page);
    expectProblemPageFits($page, 'collection-page');

    $page->resize(1440, 900);
    docsScreenshot($page, 'problem-categories');

    $page->assertNoJavaScriptErrors();
});
