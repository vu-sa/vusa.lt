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
        expect($this->user->ui_preferences)->toBeArray()
            ->and($this->user->getPinnedPages())->toBe([])
            ->and($this->user->getRecentPages())->toBe([]);
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

    test('clearRecentPages empties the list but keeps pinned pages', function (): void {
        $this->user->setPinnedPages([['route' => 'users.index', 'url' => '/mano/users']]);
        $this->user->pushRecentPage('route.a', []);
        $this->user->clearRecentPages();
        $this->user->refresh();

        expect($this->user->getRecentPages())->toBe([])
            ->and($this->user->getPinnedPages())->toHaveCount(1);
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

describe('api.v1.admin.user-preferences.update endpoint', function (): void {
    test('guests are not authorized', function (): void {
        $this->patch(route('api.v1.admin.user-preferences.update'), [
            'pinned_pages' => [['route' => 'users.index']],
        ])->assertStatus(302); // redirected to login
    });

    test('a pinned page without a route is rejected', function (): void {
        asUser($this->user)->patchJson(route('api.v1.admin.user-preferences.update'), [
            'pinned_pages' => [['title' => 'No route']],
        ])->assertStatus(422);
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
        $this->user->setPinnedPages([['route' => 'users.index', 'url' => '/mano/users']]);

        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.ui_preferences.pinned_pages.0.url', '/mano/users')
            );
    });
});
