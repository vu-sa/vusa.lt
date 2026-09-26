<?php

use App\Models\Page;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Page anatomy (title bands, tabs, badges, numbers) is covered by the Vitest component tests.
 * Here only what the built bundle can settle: each page mounts, throws nothing, fits a phone.
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

/**
 * Collections mount at their desktop width and switch to phone rows a frame or two later, so the
 * first measurement can overflow for a moment. Assert the settled layout, not that flash.
 */
function settlesWithoutSidewaysScroll(mixed $page): bool
{
    for ($attempt = 0; $attempt < 20; $attempt++) {
        if ($page->script(NO_SIDEWAYS_SCROLL)) {
            return true;
        }

        $page->wait(0.25);
    }

    return false;
}

const ADMIN_PAGES = [
    '/mano',
    '/mano/meetings',
    '/mano/meetings?showDeleted=true',
    '/mano/reservations',
    '/mano/reservations?scope=administered&state=created',
    '/mano/tags',
    '/mano/pages',
    '/mano/news',
    '/mano/calendar',
    '/mano/tenants',
    '/mano/roles',
    '/mano/permissions',
    '/mano/types',
    '/mano/relationships',
    '/mano/studyPrograms',
    '/mano/studySets',
    '/mano/users?showDeleted=true',
    '/mano/dashboard/atstovavimas',
    '/mano/dashboard/reservations',
    '/mano/dashboard/svetaine',
    '/mano/dashboard/organizacija',
    '/mano/dashboard/sistema',
    '/mano/administration',
    '/mano/profile/roles',
    '/mano/search?q=senatas',
];

it('keeps reservation actions visible beside a two-line title at table widths', function (): void {
    $page = openAdminPage('/mano/reservations', 1440, 900, function ($user): void {
        $reservation = Reservation::factory()->create([
            'name' => 'Labai ilgas rezervacijos pavadinimas studentų renginiui auditorijoje',
        ]);
        $reservation->users()->attach($user->id);
        $resource = Resource::factory()->create([
            'tenant_id' => Tenant::query()->first()->id,
            'resource_category_id' => ResourceCategory::factory()->create()->id,
            'is_reservable' => true,
        ]);
        $reservation->resources()->attach($resource->id, [
            'quantity' => 1,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'state' => 'created',
        ]);
    });

    foreach ([1440, 1180, 820] as $width) {
        $page->resize($width, 900);
        waitForInertiaRender($page, '[data-slot=collection-table]');

        expect($page->script('(function () { const table = document.querySelector("[data-slot=collection-table]"); const title = table.querySelector("[data-slot=collection-primary-cell] a"); const actions = table.querySelector("[data-slot=reservation-row-actions]"); return getComputedStyle(title).webkitLineClamp === "2" && actions.getBoundingClientRect().right <= table.getBoundingClientRect().right; })()'))->toBeTrue();
    }

    expect($page->script('(function () { const title = document.querySelector("[data-slot=collection-table] [data-slot=collection-primary-cell] a"); return title.getBoundingClientRect().height > parseFloat(getComputedStyle(title).lineHeight) * 1.5; })()'))->toBeTrue();

    $page->click('[data-slot=collection-table] [data-slot=reservation-row-actions] button[aria-label="Veiksmai"]')
        ->assertPresent('[data-slot=dropdown-menu-content]');
    expect($page->script('document.querySelector("[data-slot=dropdown-menu-content]").getBoundingClientRect().right <= window.innerWidth'))->toBeTrue();

    $page->resize(390, 844);
    $page->assertPresent('[data-slot=collection-rows] [data-slot=reservation-row-actions]')
        ->assertNoJavaScriptErrors();
    expect(settlesWithoutSidewaysScroll($page))->toBeTrue();
});

// One login for the sweep: a login per page would cost more than every assertion here combined.
it('mounts every admin page without JavaScript errors and fits a phone without scrolling sideways', function (): void {
    $page = openAdminPage('/mano', 1440, 900, function ($user): void {
        Task::factory()->create(['due_date' => now()->subDays(2)])->users()->attach($user->id);
    });

    $failures = [];

    foreach ([1440, 390] as $width) {
        $page->resize($width, $width === 390 ? 844 : 900);

        foreach (ADMIN_PAGES as $path) {
            $page->navigate($path);
            waitForInertiaRender($page, '[data-slot=admin-shell] main');

            foreach ($page->page()->javaScriptErrors() as $error) {
                $failures[] = "{$path} at {$width}px: {$error['message']}";
            }

            if ($width === 390 && ! settlesWithoutSidewaysScroll($page)) {
                $failures[] = "{$path}: scrolls sideways at 390px";
            }
        }
    }

    expect($failures)->toBeEmpty(implode("\n", $failures));
});

describe('Puslapiai', function (): void {
    it('keeps sorting and results together while deleted pages live in filters', function (): void {
        $page = openAdminPage('/mano/pages', 1180, 900, function (): void {
            Page::factory()->count(2)->create()->each(fn (Page $item) => $item->delete());
        });

        $page->assertPresent('[data-slot=collection-results-toolbar] select[aria-label="Rikiuoti"]')
            ->assertPresent('[data-slot=collection-results-toolbar] button:has-text("Stulpeliai")');

        expect($page->script('document.querySelector("[data-slot=collection-control-row]").textContent.includes("Ištrinti")'))->toBeFalse()
            ->and($page->script('(function () { const select = document.querySelector("[data-slot=collection-results-toolbar] select"); const rect = select.getBoundingClientRect(); return document.elementFromPoint(rect.right - 16, (rect.top + rect.bottom) / 2) === select; })()'))->toBeTrue();

        $page->click('[data-slot=collection-control-row] button:has-text("Filtrai")')
            ->assertPresent('[data-slot=collection-filter-bar] button[aria-pressed="false"]')
            ->assertNoJavaScriptErrors();

        expect($page->script('(function () { const badge = document.querySelector("[data-slot=collection-filter-bar] button span"); badge.textContent = "123"; return badge.scrollWidth <= badge.clientWidth; })()'))->toBeTrue();

        $page->navigate('/mano/pages?showDeleted=true');
        waitForInertiaRender($page, '[data-slot=collection-table]');
        $page->assertNoJavaScriptErrors();

        $page->resize(820, 900);
        expect($page->script('document.querySelector("[data-slot=collection-table] td:last-child").getBoundingClientRect().right <= document.querySelector("[data-slot=collection-table]").getBoundingClientRect().right'))->toBeTrue()
            ->and($page->script('getComputedStyle(document.querySelector("[data-slot=table-header]")).backgroundColor === getComputedStyle(document.querySelector("[data-slot=table-header] th:last-child")).backgroundColor'))->toBeTrue()
            ->and($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();

        $page->resize(390, 844);
        $page->assertPresent('[data-slot=collection-rows]');
        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue()
            ->and($page->script('document.querySelector("[data-slot=collection-rows]").textContent.includes("page-")'))->toBeFalse()
            ->and($page->script('(function () { const row = document.querySelector("[data-slot=collection-rows] li"); const actions = row.querySelector("[data-slot=collection-row-actions]").getBoundingClientRect(); const status = row.querySelector("[data-slot=status-badge]").getBoundingClientRect(); return Math.abs((actions.top + actions.bottom) / 2 - (status.top + status.bottom) / 2) < 2; })()'))->toBeTrue();

        $page->resize(1440, 900);
        $page->assertPresent('[data-slot=collection-table]');
        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue()
            ->and($page->script('getComputedStyle(document.querySelector("[data-slot=collection-table] [data-slot=collection-primary-cell] p")).webkitLineClamp'))->toBe('2');

        $page->navigate('/mano/pages?showDeleted=true&view=preview');
        waitForInertiaRender($page, '[data-slot=collection-rows]');
        $page->assertPresent('[data-slot=collection-preview] [data-slot=collection-row-actions]');
        expect($page->script('document.querySelector("[data-slot=collection-rows] [data-slot=collection-row-actions]")'))->toBeNull()
            ->and($page->script('document.querySelector("[data-slot=collection-rows] [data-slot=status-badge]") !== null'))->toBeTrue()
            ->and($page->script('document.querySelector("[data-slot=collection-preview] dl").textContent.includes("Padalinys")'))->toBeTrue();

        $page->click('[data-slot=collection-rows] li:nth-child(2) article p');
        expect($page->script('document.querySelector("[data-slot=collection-preview] h2").textContent.trim() === document.querySelector("[data-slot=collection-rows] li:nth-child(2) [data-collection-open]").textContent.trim()'))->toBeTrue()
            ->and($page->script('(function () { const area = document.querySelector("[data-slot=admin-scroll-area]"); const rows = document.querySelector("[data-slot=collection-rows]"); rows.style.minHeight = "1800px"; area.scrollTop = 600; const pane = document.querySelector("[data-slot=collection-preview]").getBoundingClientRect(); const chrome = area.firstElementChild.getBoundingClientRect(); return pane.top >= chrome.bottom + 8 && pane.bottom <= area.getBoundingClientRect().bottom - 8; })()'))->toBeTrue();
    });
});

describe('Žymos', function (): void {
    it('opens the tag editor as a phone-sized sheet without JavaScript errors', function (): void {
        $page = openAdminPage('/mano/tags', 390, 844);

        $page->click('button:has-text("Nauja žyma")')
            ->assertSee('Nauja žyma')
            ->assertNoJavaScriptErrors();

        expect($page->script(NO_SIDEWAYS_SCROLL))->toBeTrue();
    });
});
