<?php

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The first two pages built on the admin page types: Pradžia
 * and the Posėdžiai collection. What only a browser can settle is that they mount against the real
 * bundle, draw their anatomy, fit a phone without scrolling sideways, and throw nothing.
 */
function openAdminPage(string $path, int $width, int $height = 900, ?Closure $arrange = null): mixed
{
    $user = makeAdminUser(Tenant::query()->first());

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
        waitForInertiaRender($page, '[data-slot=overview-section]');

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

    it('opens a deep link from an overview number with its filter already applied', function (): void {
        $page = openAdminPage('/mano/reservations?scope=administered&state=created', 1440);

        $page->assertPresent('[data-slot=collection-page]')->assertNoJavaScriptErrors();
        expect($page->script('window.location.search'))->toContain('state=created');
    });

    it('opens the tag editor as a phone-sized sheet without JavaScript errors', function (): void {
        $page = openAdminPage('/mano/tags', 390, 844);

        $page->click('button:has-text("Nauja žyma")')
            ->assertSee('Nauja žyma')
            ->assertNoJavaScriptErrors();

        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});

/**
 * PR 5.8 – 5.10: the Overview layout on its second and third pages, and the search reduced to
 * cross-entity results. Same bar as above: mounts against the real bundle, fits a phone, throws nothing.
 */
describe('ViSAK overview', function (): void {
    it('states where it is and shows its numbers as links', function (): void {
        $page = openAdminPage('/mano/dashboard/atstovavimas', 1440);
        waitForInertiaRender($page, '[data-slot=overview-numbers]');

        $page->assertPresent('[data-slot=overview-page]')->assertPresent('[data-slot=overview-scope-switch]');
        expect($page->script("document.querySelector('[data-slot=overview-title-band]').textContent"))->toContain('ViSAK')
            ->and($page->script("document.querySelectorAll('[data-slot=overview-numbers] a').length"))->toBe(4)
            ->and($page->script("document.querySelectorAll('[role=tab]').length"))->toBe(0);

        $page->assertNoJavaScriptErrors();
    });

    it('fits a phone without scrolling sideways', function (): void {
        $page = openAdminPage('/mano/dashboard/atstovavimas', 390, 844);
        waitForInertiaRender($page, '[data-slot=overview-numbers]');

        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});

describe('Mano rolės ir pareigybės', function (): void {
    it('lists what the user holds and is reachable from the account menu target', function (): void {
        $page = openAdminPage('/mano/profile/roles', 1440);

        $page->assertPresent('[data-slot=overview-page]');
        expect($page->script("document.querySelector('[data-slot=overview-page] h1').textContent.trim()"))->toBe('Mano rolės ir pareigybės');

        $page->assertNoJavaScriptErrors();
    });

    it('fits a phone without scrolling sideways', function (): void {
        $page = openAdminPage('/mano/profile/roles', 390, 844);

        $page->assertPresent('[data-slot=overview-page]');
        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});

describe('Paieška', function (): void {
    it('is one search field over grouped results, with no entity tabs', function (): void {
        $page = openAdminPage('/mano/search?q=senatas', 1440);

        $page->assertPresent('[data-slot=overview-page]')->assertPresent('input[data-admin-collection-search]');
        expect($page->script("document.querySelectorAll('[role=tab]').length"))->toBe(0);
    });

    it('sends an entity tab to that entity\'s own page', function (): void {
        $page = openAdminPage('/mano/search?tab=institutions&q=senatas', 1440);

        expect($page->script('window.location.pathname'))->toBe('/mano/institutions')
            ->and($page->script('window.location.search'))->toContain('search=senatas');
    });

    it('fits a phone without scrolling sideways', function (): void {
        $page = openAdminPage('/mano/search', 390, 844);

        $page->assertPresent('[data-slot=overview-page]');
        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});

/**
 * PR 7.1 – 7.5: the four remaining workspace overviews and Visi skyriai. Same bar again: each
 * mounts against the real bundle on the shared Overview layout, fits a phone and throws nothing.
 */
describe('Workspace overviews', function (): void {
    it('opens every overview on the shared layout with its numbers as links', function (string $path, string $eyebrow): void {
        $page = openAdminPage($path, 1440);
        waitForInertiaRender($page, '[data-slot=overview-page]');

        $page->assertPresent('[data-slot=overview-title-band]')->assertNoJavaScriptErrors();
        expect($page->script("document.querySelector('[data-slot=overview-title-band]').textContent"))->toContain($eyebrow)
            ->and($page->script("document.querySelectorAll('[data-slot=overview-numbers] a').length"))->toBeGreaterThan(0);
    })->with([
        'Rezervacijos' => ['/mano/dashboard/reservations', 'Rezervacijos'],
        'Svetainė' => ['/mano/dashboard/svetaine', 'Svetainė'],
        'Organizacija' => ['/mano/dashboard/organizacija', 'Organizacija'],
        'Sistema' => ['/mano/dashboard/sistema', 'Sistema'],
    ]);

    it('fits a phone without scrolling sideways', function (string $path): void {
        $page = openAdminPage($path, 390, 844);
        waitForInertiaRender($page, '[data-slot=overview-page]');

        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    })->with([
        'Rezervacijos' => ['/mano/dashboard/reservations'],
        'Svetainė' => ['/mano/dashboard/svetaine'],
        'Organizacija' => ['/mano/dashboard/organizacija'],
        'Sistema' => ['/mano/dashboard/sistema'],
        'Visi skyriai' => ['/mano/administration'],
    ]);
});

describe('Visi skyriai', function (): void {
    it('lists every workspace as hairline rows, Pradžia included', function (): void {
        $page = openAdminPage('/mano/administration', 1440);
        waitForInertiaRender($page, '[data-slot=overview-page]');

        $page->assertNoJavaScriptErrors();
        expect($page->script("document.querySelectorAll('[data-workspace]').length"))->toBeGreaterThanOrEqual(5)
            ->and($page->script("document.querySelectorAll('[data-workspace=pradzia] a').length"))->toBe(3);
    });
});
