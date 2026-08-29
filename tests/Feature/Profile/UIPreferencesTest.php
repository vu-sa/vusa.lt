<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = User::factory()->create();
});

describe('HasUIPreferences trait', function (): void {
    test('applies defaults when column is null', function (): void {
        expect($this->user->ui_preferences)->toBeArray();

        $visibility = $this->user->getSidebarSectionVisibility();
        expect($visibility['pinned'])->toBeTrue()
            ->and($visibility['recently_visited'])->toBeTrue()
            ->and($visibility['followed_institutions'])->toBeFalse()
            ->and($visibility['spacer'])->toBeTrue()
            ->and($this->user->getRecentPages())->toBe([]);
    });

    test('default section order contains every toggleable section once', function (): void {
        $order = $this->user->getSidebarSectionOrder();
        expect($order)->toContain('pinned')
            ->toContain('recently_visited')
            ->toContain('spacer')
            ->and($order)->toHaveSameSize(array_unique($order));
    });

    test('setSidebarSectionOrder sanitizes and appends missing sections', function (): void {
        $this->user->setSidebarSectionOrder(['start_fm', 'pinned', 'unknown']);
        $this->user->refresh();

        $order = $this->user->getSidebarSectionOrder();
        expect($order)->toMatchArray([0 => 'start_fm', 1 => 'pinned'])->not->toContain('unknown')
            ->toContain('secondary')
            ->toContain('spacer');
    });

    test('setSidebarSectionVisibility persists and ignores unknown keys', function (): void {
        $this->user->setSidebarSectionVisibility([
            'pinned' => false,
            'bogus_section' => false,
        ]);

        $this->user->refresh();

        $visibility = $this->user->getSidebarSectionVisibility();
        expect($visibility['pinned'])->toBeFalse()
            ->and($visibility)->not->toHaveKey('bogus_section');
    });

    test('pushRecentPage dedupes and caps the list', function (): void {
        for ($i = 0; $i < 18; $i++) {
            $this->user->pushRecentPage("route.{$i}", []);
        }
        // Re-visit an existing route — should move to front, not duplicate.
        $this->user->pushRecentPage('route.17', []);
        $this->user->refresh();

        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(15)
            ->and($recent[0]['route'])->toBe('route.17')
            ->and(collect($recent)->pluck('route')->duplicates())->toBeEmpty();
    });

    test('pushRecentPage dedupes by path, ignoring query string', function (): void {
        $this->user->pushRecentPage('users.index', [], 'Users', '/mano/users');
        $this->user->pushRecentPage('users.index', ['page' => 2], 'Users', '/mano/users');
        $this->user->pushRecentPage('news.index', [], 'News', '/mano/news');
        $this->user->refresh();

        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(2)
            ->and(collect($recent)->pluck('url')->toArray())->toBe(['/mano/news', '/mano/users']);
    });

    test('clearRecentPages empties the list but keeps section visibility', function (): void {
        $this->user->setSidebarSectionVisibility(['start_fm' => false]);
        $this->user->pushRecentPage('route.a', []);
        $this->user->clearRecentPages();
        $this->user->refresh();

        expect($this->user->getRecentPages())->toBe([])
            ->and($this->user->getSidebarSectionVisibility()['start_fm'])->toBeFalse();
    });
});

describe('pinned pages', function (): void {
    test('defaults to an empty list', function (): void {
        expect($this->user->getPinnedPages())->toBe([]);
    });

    test('setPinnedPages sanitizes, dedupes by path, and caps the list', function (): void {
        $pages = [];
        for ($i = 0; $i < 12; $i++) {
            $pages[] = ['route' => "route.{$i}", 'url' => "/mano/r{$i}"];
        }
        // A duplicate path and a malformed entry that must be dropped.
        $pages[] = ['route' => 'route.0', 'url' => '/mano/r0'];
        $pages[] = ['params' => []]; // no route → discarded

        $this->user->setPinnedPages($pages);
        $this->user->refresh();

        $pinned = $this->user->getPinnedPages();
        expect($pinned)->toHaveCount(10); // capped at MAX
        expect(collect($pinned)->pluck('url')->duplicates())->toBeEmpty();
    });

    test('endpoint stores pinned pages and returns 204', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'pinned_pages' => [
                ['route' => 'users.index', 'params' => [], 'title' => 'Users', 'url' => '/mano/users'],
            ],
        ])->assertNoContent();

        $this->user->refresh();
        $pinned = $this->user->getPinnedPages();
        expect($pinned)->toHaveCount(1)
            ->and($pinned[0])->toMatchArray(['route' => 'users.index', 'url' => '/mano/users']);
    });
});

describe('density', function (): void {
    test('defaults to comfortable', function (): void {
        expect($this->user->getDensity())->toBe('comfortable');
    });

    test('setDensity persists a valid value and ignores unknown ones', function (): void {
        $this->user->setDensity('compact');
        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');

        $this->user->setDensity('bogus');
        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');
    });

    test('endpoint rejects an invalid density', function (): void {
        asUser($this->user)->patchJson(route('api.v1.admin.user-preferences.update'), [
            'appearance' => ['density' => 'bogus'],
        ])->assertStatus(422);
    });

    test('endpoint stores a valid density', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'appearance' => ['density' => 'compact'],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');
    });
});

describe('sidebar collapsed', function (): void {
    test('defaults to false', function (): void {
        expect($this->user->getSidebarCollapsed())->toBeFalse();
    });

    test('endpoint persists the collapsed flag', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['collapsed' => true],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getSidebarCollapsed())->toBeTrue();
    });
});

describe('api.v1.admin.user-preferences.update endpoint', function (): void {
    test('guests are not authorized', function (): void {
        $this->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['sections' => ['pinned' => false]],
        ])->assertStatus(302); // redirected to login
    });

    test('an authenticated user can toggle a section and gets 204', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['sections' => ['pinned' => false]],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getSidebarSectionVisibility()['pinned'])->toBeFalse();
    });

    test('an authenticated user can reorder sections', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['order' => ['recently_visited', 'secondary', 'bogus']],
        ])->assertNoContent();

        $this->user->refresh();
        $order = $this->user->getSidebarSectionOrder();
        expect($order)->toMatchArray([0 => 'recently_visited', 1 => 'secondary'])->not->toContain('bogus');
        // Missing toggleable sections are appended.
        expect($order)->toContain('pinned');
        expect($order)->toContain('start_fm');
    });
});

describe('api.v1.admin.user-preferences.trackRecentPage endpoint', function (): void {
    test('records a visited page and returns 204', function (): void {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.trackRecentPage'), [
            'route' => 'meetings.index',
            'params' => [],
        ])->assertNoContent();

        $this->user->refresh();
        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(1)
            ->and($recent[0]['route'])->toBe('meetings.index');
    });

    test('clear flag empties the recent list', function (): void {
        $this->user->pushRecentPage('meetings.index', []);

        asUser($this->user)->patch(route('api.v1.admin.user-preferences.trackRecentPage'), [
            'clear' => true,
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getRecentPages())->toBe([]);
    });
});

describe('Inertia payload', function (): void {
    test('ui_preferences is shared on auth.user', function (): void {
        $this->user->setSidebarSectionVisibility(['secondary' => false]);
        $this->user->setDensity('compact');
        $this->user->setSidebarCollapsed(true);

        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.ui_preferences.sidebar.sections.secondary', false)
                ->where('auth.user.ui_preferences.appearance.density', 'compact')
                ->where('auth.user.ui_preferences.sidebar.collapsed', true)
            );
    });
});
