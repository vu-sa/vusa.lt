<?php

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The first two pages built in the redesign's language (.ai/redesign/admin, PR 5.1 + 5.2): Pradžia
 * and the Posėdžiai collection. What only a browser can settle is that they mount against the real
 * bundle, draw their anatomy, fit a phone without scrolling sideways, and throw nothing.
 */
function openAdminPage(string $path, int $width, int $height = 900, ?Closure $arrange = null): mixed
{
    $user = makeAdminUser(Tenant::query()->first());
    $user->setNewAdminShellEnabled(true);

    if ($arrange) {
        $arrange($user);
    }

    $page = loginAsAdmin($user);
    $page->resize($width, $height)->navigate($path);
    waitForInertiaRender($page, '[data-slot=admin-shell] main');

    return $page;
}

const NO_SIDEWAYS_SCROLL = 'document.documentElement.scrollWidth <= window.innerWidth';

describe('Pradžia', function (): void {
    it('teaches, rather than shows an empty box, when nothing is waiting', function (): void {
        $page = openAdminPage('/mano', 1440);

        $page->assertPresent('[data-slot=attention-queue] [data-slot=empty-state]');
        expect($page->script("document.querySelector('[data-slot=attention-queue] .bg-foreground')"))->toBeNull();

        // The deferred group (Neseniai redaguota, koordinatorius…) arrives after the first paint.
        waitForInertiaRender($page, '[data-slot=home-section]');

        $page->assertNoJavaScriptErrors();
    });

    it('opens with the ink band and an overdue badge when something needs doing', function (): void {
        $page = openAdminPage('/mano', 1440, 900, function ($user): void {
            Task::factory()->create(['name' => 'Užpildyti darbotvarkę', 'due_date' => now()->subDays(2)])->users()->attach($user->id);
        });

        expect($page->script("document.querySelector('[data-slot=attention-queue] .bg-foreground h2').textContent"))->toContain('Užduočių')
            ->and($page->script("document.querySelector('[data-slot=attention-queue] [data-slot=status-badge]').dataset.statusRole"))->toBe('danger');

        $page->assertNoJavaScriptErrors();
    });

    it('fits a phone without scrolling sideways', function (): void {
        $page = openAdminPage('/mano', 390, 844, function ($user): void {
            Task::factory()->create(['due_date' => now()->addDays(2)])->users()->attach($user->id);
        });

        $page->assertPresent('[data-slot=attention-queue]');
        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});

describe('Posėdžiai', function (): void {
    it('states where it is and offers search, filters and the three views on a desktop', function (): void {
        $page = openAdminPage('/mano/meetings', 1440);

        $page->assertPresent('[data-slot=collection-page]')->assertPresent('[data-slot=collection-title-band]');
        expect($page->script("document.querySelector('[data-slot=collection-title-band] h1').textContent.trim()"))->toBe('Posėdžiai')
            ->and($page->script("document.querySelector('[data-slot=collection-title-band]').textContent"))->toContain('ViSAK')
            ->and($page->script("document.querySelectorAll('[data-slot=collection-view-toggle] [role=radio]').length"))->toBeGreaterThanOrEqual(2);

        // `/` focuses this field (U3); it is how a rep finds yesterday's meeting.
        $page->assertPresent('input[data-admin-collection-search]');
        $page->assertNoJavaScriptErrors();
    });

    it('lights the Posėdžiai tab and draws no breadcrumbs at section level', function (): void {
        $page = openAdminPage('/mano/meetings', 1440);

        expect($page->script("document.querySelector('[data-slot=section-tabs] [aria-current=page]').textContent"))->toContain('Posėdžiai')
            ->and($page->script("document.querySelector('[data-slot=shell-breadcrumbs]')"))->toBeNull();
    });

    it('is rows only on a phone, without sideways scrolling', function (): void {
        $page = openAdminPage('/mano/meetings', 390, 844);

        $page->assertPresent('[data-slot=collection-title-band]');
        expect($page->script("document.querySelector('[data-slot=collection-view-toggle]')"))->toBeNull()
            ->and($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();

        $page->assertNoJavaScriptErrors();
    });

    it('still opens the trash as a database table', function (): void {
        $page = openAdminPage('/mano/meetings?showDeleted=true', 1440);

        $page->assertPresent('table, [data-slot=empty-state]');
        $page->assertNoJavaScriptErrors();
    });
});

describe('Rezervacijos ir žymos', function (): void {
    it('renders the reservation queue as a database-backed collection without JavaScript errors', function (): void {
        $page = openAdminPage('/mano/reservations', 1440);

        $page->assertPresent('[data-slot=collection-page]')
            ->assertPresent('input[data-admin-collection-search]')
            ->assertNoJavaScriptErrors();

        expect($page->script("document.querySelector('[data-slot=collection-title-band] h1').textContent.trim()"))
            ->toBe('Rezervacijos');
    });

    it('opens the tag editor as a phone-sized sheet without JavaScript errors', function (): void {
        $page = openAdminPage('/mano/tags', 390, 844);

        $page->click('button:has-text("Nauja žyma")')
            ->assertSee('Nauja žyma')
            ->assertNoJavaScriptErrors();

        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});
