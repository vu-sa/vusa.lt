<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = User::factory()->create();
});

describe('HasUIPreferences trait', function () {
    test('applies defaults when column is null', function () {
        expect($this->user->ui_preferences)->toBeArray();

        $visibility = $this->user->getSidebarSectionVisibility();
        expect($visibility['quick_actions'])->toBeTrue();
        expect($visibility['recently_visited'])->toBeTrue();
        expect($visibility['followed_institutions'])->toBeFalse();
        expect($visibility['spacer'])->toBeTrue();
        expect($this->user->getRecentPages())->toBe([]);
    });

    test('default section order contains every toggleable section once', function () {
        $order = $this->user->getSidebarSectionOrder();
        expect($order)->toContain('quick_actions');
        expect($order)->toContain('recently_visited');
        expect($order)->toContain('spacer');
        expect(count($order))->toBe(count(array_unique($order)));
    });

    test('setSidebarSectionOrder sanitizes and appends missing sections', function () {
        $this->user->setSidebarSectionOrder(['start_fm', 'quick_actions', 'unknown']);
        $this->user->refresh();

        $order = $this->user->getSidebarSectionOrder();
        expect($order[0])->toBe('start_fm');
        expect($order[1])->toBe('quick_actions');
        expect($order)->not->toContain('unknown');
        expect($order)->toContain('secondary');
        expect($order)->toContain('spacer');
    });

    test('setSidebarSectionVisibility persists and ignores unknown keys', function () {
        $this->user->setSidebarSectionVisibility([
            'quick_actions' => false,
            'bogus_section' => false,
        ]);

        $this->user->refresh();

        $visibility = $this->user->getSidebarSectionVisibility();
        expect($visibility['quick_actions'])->toBeFalse();
        expect($visibility)->not->toHaveKey('bogus_section');
    });

    test('pushRecentPage dedupes and caps the list', function () {
        for ($i = 0; $i < 18; $i++) {
            $this->user->pushRecentPage("route.{$i}", []);
        }
        // Re-visit an existing route — should move to front, not duplicate.
        $this->user->pushRecentPage('route.17', []);
        $this->user->refresh();

        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(15);
        expect($recent[0]['route'])->toBe('route.17');
        expect(collect($recent)->pluck('route')->duplicates())->toBeEmpty();
    });

    test('pushRecentPage dedupes by path, ignoring query string', function () {
        $this->user->pushRecentPage('users.index', [], 'Users', '/mano/users');
        $this->user->pushRecentPage('users.index', ['page' => 2], 'Users', '/mano/users');
        $this->user->pushRecentPage('news.index', [], 'News', '/mano/news');
        $this->user->refresh();

        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(2);
        expect(collect($recent)->pluck('url')->toArray())->toBe(['/mano/news', '/mano/users']);
    });

    test('clearRecentPages empties the list but keeps section visibility', function () {
        $this->user->setSidebarSectionVisibility(['start_fm' => false]);
        $this->user->pushRecentPage('route.a', []);
        $this->user->clearRecentPages();
        $this->user->refresh();

        expect($this->user->getRecentPages())->toBe([]);
        expect($this->user->getSidebarSectionVisibility()['start_fm'])->toBeFalse();
    });
});

describe('pinned pages', function () {
    test('defaults to an empty list', function () {
        expect($this->user->getPinnedPages())->toBe([]);
    });

    test('setPinnedPages sanitizes, dedupes by path, and caps the list', function () {
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

    test('endpoint stores pinned pages and returns 204', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'pinned_pages' => [
                ['route' => 'users.index', 'params' => [], 'title' => 'Users', 'url' => '/mano/users'],
            ],
        ])->assertNoContent();

        $this->user->refresh();
        $pinned = $this->user->getPinnedPages();
        expect($pinned)->toHaveCount(1);
        expect($pinned[0]['route'])->toBe('users.index');
        expect($pinned[0]['url'])->toBe('/mano/users');
    });
});

describe('density', function () {
    test('defaults to comfortable', function () {
        expect($this->user->getDensity())->toBe('comfortable');
    });

    test('setDensity persists a valid value and ignores unknown ones', function () {
        $this->user->setDensity('compact');
        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');

        $this->user->setDensity('bogus');
        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');
    });

    test('endpoint rejects an invalid density', function () {
        asUser($this->user)->patchJson(route('api.v1.admin.user-preferences.update'), [
            'appearance' => ['density' => 'bogus'],
        ])->assertStatus(422);
    });

    test('endpoint stores a valid density', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'appearance' => ['density' => 'compact'],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getDensity())->toBe('compact');
    });
});

describe('sidebar collapsed', function () {
    test('defaults to false', function () {
        expect($this->user->getSidebarCollapsed())->toBeFalse();
    });

    test('endpoint persists the collapsed flag', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['collapsed' => true],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getSidebarCollapsed())->toBeTrue();
    });
});

describe('quick action visibility', function () {
    test('applies defaults when column is null', function () {
        $visibility = $this->user->getQuickActionVisibility();
        expect($visibility['new_meeting'])->toBeTrue();
        expect($visibility['new_news'])->toBeTrue();
        expect($visibility['duty_update'])->toBeTrue();
    });

    test('setQuickActionVisibility persists and ignores unknown keys', function () {
        $this->user->setQuickActionVisibility([
            'new_meeting' => false,
            'bogus_action' => false,
        ]);
        $this->user->refresh();

        $visibility = $this->user->getQuickActionVisibility();
        expect($visibility['new_meeting'])->toBeFalse();
        expect($visibility)->not->toHaveKey('bogus_action');
    });

    test('reset does not wipe quick action visibility', function () {
        $this->user->setQuickActionVisibility(['new_meeting' => false]);
        $this->user->setSidebarSectionVisibility(['start_fm' => false]);

        // Simulate a full reset via the endpoint
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => [
                'sections' => [
                    'quick_actions' => true,
                    'recently_visited' => true,
                    'followed_institutions' => true,
                    'spacer' => true,
                    'start_fm' => true,
                    'secondary' => true,
                ],
                'order' => ['quick_actions', 'recently_visited', 'followed_institutions', 'spacer', 'start_fm', 'secondary'],
            ],
            'quick_actions' => [
                'new_problem' => true,
                'new_meeting' => true,
                'new_news' => true,
                'new_reservation' => true,
                'duty_update' => true,
            ],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getQuickActionVisibility()['new_meeting'])->toBeTrue();
        expect($this->user->getSidebarSectionVisibility()['start_fm'])->toBeTrue();
    });
});

describe('api.v1.admin.user-preferences.update endpoint', function () {
    test('guests are not authorized', function () {
        $this->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['sections' => ['quick_actions' => false]],
        ])->assertStatus(302); // redirected to login
    });

    test('an authenticated user can toggle a section and gets 204', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['sections' => ['quick_actions' => false]],
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getSidebarSectionVisibility()['quick_actions'])->toBeFalse();
    });

    test('an authenticated user can reorder sections', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.update'), [
            'sidebar' => ['order' => ['recently_visited', 'secondary', 'bogus']],
        ])->assertNoContent();

        $this->user->refresh();
        $order = $this->user->getSidebarSectionOrder();
        expect($order[0])->toBe('recently_visited');
        expect($order[1])->toBe('secondary');
        expect($order)->not->toContain('bogus');
        // Missing toggleable sections are appended.
        expect($order)->toContain('quick_actions');
        expect($order)->toContain('start_fm');
    });
});

describe('api.v1.admin.user-preferences.trackRecentPage endpoint', function () {
    test('records a visited page and returns 204', function () {
        asUser($this->user)->patch(route('api.v1.admin.user-preferences.trackRecentPage'), [
            'route' => 'meetings.index',
            'params' => [],
        ])->assertNoContent();

        $this->user->refresh();
        $recent = $this->user->getRecentPages();
        expect($recent)->toHaveCount(1);
        expect($recent[0]['route'])->toBe('meetings.index');
    });

    test('clear flag empties the recent list', function () {
        $this->user->pushRecentPage('meetings.index', []);

        asUser($this->user)->patch(route('api.v1.admin.user-preferences.trackRecentPage'), [
            'clear' => true,
        ])->assertNoContent();

        $this->user->refresh();
        expect($this->user->getRecentPages())->toBe([]);
    });
});

describe('Inertia payload', function () {
    test('ui_preferences is shared on auth.user', function () {
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
