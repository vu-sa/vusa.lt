<?php

use App\Models\Navigation;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a basic navigation structure
    $this->rootNav = Navigation::factory()->create([
        'name' => 'Root Nav',
        'url' => '/root',
        'parent_id' => 0,
        'order' => 1,
        'lang' => 'lt',
        'extra_attributes' => null,
    ]);

    $this->childNav = Navigation::factory()->create([
        'name' => 'Child Nav',
        'url' => '/child',
        'parent_id' => $this->rootNav->id,
        'order' => 1,
        'lang' => 'lt',
        'extra_attributes' => ['column' => 1],
    ]);
});

describe('NavigationService caching', function (): void {
    test('getNavigationForPublic returns cached result on second call', function (): void {
        // Clear any existing cache
        NavigationService::clearCache();

        // First call should query the database
        $result1 = NavigationService::getNavigationForPublic();

        // Second call should use cache - verify by checking the result is identical
        $result2 = NavigationService::getNavigationForPublic();

        expect($result1)->toBeArray()->toEqual($result2)->not->toBeEmpty();
    });

    test('cache is keyed by locale', function (): void {
        NavigationService::clearCache();

        // Create English navigation
        $enRoot = Navigation::factory()->create([
            'name' => 'English Root',
            'url' => '/en-root',
            'parent_id' => 0,
            'order' => 1,
            'lang' => 'en',
        ]);

        // Get LT navigation
        app()->setLocale('lt');
        $ltResult = NavigationService::getNavigationForPublic();

        // Get EN navigation
        app()->setLocale('en');
        $enResult = NavigationService::getNavigationForPublic();

        // They should be different because different language content
        expect($ltResult)->not->toEqual($enResult);
    });

    test('clearCache clears all locale caches', function (): void {
        NavigationService::clearCache();

        // Populate caches
        app()->setLocale('lt');
        NavigationService::getNavigationForPublic();

        app()->setLocale('en');
        NavigationService::getNavigationForPublic();

        // Verify caches exist
        expect(Cache::has('navigation:public:lt'))->toBeTrue();
        expect(Cache::has('navigation:public:en'))->toBeTrue();

        // Clear caches
        NavigationService::clearCache();

        // Verify caches are cleared
        expect(Cache::has('navigation:public:lt'))->toBeFalse();
        expect(Cache::has('navigation:public:en'))->toBeFalse();
    });

    test('cache is invalidated when navigation is saved', function (): void {
        NavigationService::clearCache();

        // Populate cache
        app()->setLocale('lt');
        $originalResult = NavigationService::getNavigationForPublic();

        // Update a navigation item (triggers model's saved event)
        $this->rootNav->name = 'Updated Root Nav';
        $this->rootNav->save();

        // Cache should be cleared - fetch again should reflect changes
        $newResult = NavigationService::getNavigationForPublic();

        // The new result should have the updated name
        $rootNames = array_column($newResult, 'name');
        expect($rootNames)->toContain('Updated Root Nav');
    });

    test('cache is invalidated when navigation is deleted', function (): void {
        NavigationService::clearCache();

        // Create an extra root item
        $extraNav = Navigation::factory()->create([
            'name' => 'Extra Nav',
            'url' => '/extra',
            'parent_id' => 0,
            'order' => 2,
            'lang' => 'lt',
        ]);

        // Populate cache
        app()->setLocale('lt');
        $originalResult = NavigationService::getNavigationForPublic();
        $originalCount = count($originalResult);

        // Delete the extra item (triggers model's deleted event)
        $extraNav->delete();

        // Cache should be cleared - fetch again should have fewer items
        $newResult = NavigationService::getNavigationForPublic();

        expect(count($newResult))->toBeLessThan($originalCount);
    });
});

describe('NavigationService output structure', function (): void {
    test('returns correct structure with links and columns', function (): void {
        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getNavigationForPublic();

        expect($result)->toBeArray()->not->toBeEmpty();

        // Root element should have 'links' and 'cols' keys
        $rootElement = $result[0];
        expect($rootElement)->toHaveKeys(['name', 'links', 'cols']);
    });

    test('children are organized into correct columns', function (): void {
        // Create second child in column 2
        Navigation::factory()->create([
            'name' => 'Column 2 Child',
            'url' => '/child-col2',
            'parent_id' => $this->rootNav->id,
            'order' => 2,
            'lang' => 'lt',
            'extra_attributes' => ['column' => 2],
        ]);

        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getNavigationForPublic();

        // Find our test root element (not the seeded ones)
        $rootElement = collect($result)->firstWhere('id', $this->rootNav->id);

        // Should have 2 columns
        expect($rootElement['cols'])->toBe(2);
    });

    test('root extra_attributes are hoisted onto the root, same as children', function (): void {
        $this->rootNav->extra_attributes = ['menu_width' => 'narrow', 'icon' => 'star-16-regular'];
        $this->rootNav->save();

        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getNavigationForPublic();
        $rootElement = collect($result)->firstWhere('id', $this->rootNav->id);

        expect($rootElement)->not->toHaveKey('extra_attributes')
            ->and($rootElement['menu_width'])->toBe('narrow')
            ->and($rootElement['icon'])->toBe('star-16-regular');
    });
});

describe('footer navigation', function (): void {
    test('getNavigationForPublic excludes footer-location roots from the header tree', function (): void {
        $footerRoot = Navigation::factory()->create([
            'name' => 'Footer column',
            'url' => '#',
            'parent_id' => 0,
            'order' => 2,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getNavigationForPublic();

        expect(collect($result)->pluck('id'))->not->toContain($footerRoot->id);
    });

    test('getTreeForAdmin excludes footer-location roots', function (): void {
        $footerRoot = Navigation::factory()->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        $tree = NavigationService::getTreeForAdmin('lt');

        expect(collect($tree)->pluck('id'))
            ->toContain($this->rootNav->id)
            ->not->toContain($footerRoot->id);
    });

    test('getFooterNavigationForPublic returns only footer-location roots with their children', function (): void {
        $footerRoot = Navigation::factory()->create([
            'name' => 'Apie mus',
            'url' => '#',
            'parent_id' => 0,
            'order' => 5,
            'lang' => 'lt',
            'is_active' => true,
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        $footerLink = Navigation::factory()->create([
            'name' => 'Kontaktai',
            'url' => '/kontaktai',
            'parent_id' => $footerRoot->id,
            'order' => 1,
            'lang' => 'lt',
            'is_active' => true,
            'extra_attributes' => ['location' => 'footer', 'type' => 'link', 'new_tab' => true],
        ]);

        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getFooterNavigationForPublic();

        expect($result)->toHaveCount(1);
        $column = $result[0];
        expect($column['id'])->toBe($footerRoot->id)
            ->and($column['name'])->toBe('Apie mus')
            ->and($column['links'])->toHaveCount(1)
            ->and($column['links'][0]['id'])->toBe($footerLink->id)
            ->and($column['links'][0]['new_tab'])->toBeTrue();

        // The header tree's root created in beforeEach() must not leak into the footer result.
        expect(collect($result)->pluck('id'))->not->toContain($this->rootNav->id);
    });

    test('getFooterNavigationForPublic caps at FOOTER_MAX_COLUMNS', function (): void {
        Navigation::factory()->count(NavigationService::FOOTER_MAX_COLUMNS + 2)->sequence(fn ($sequence) => ['order' => $sequence->index])->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'is_active' => true,
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        NavigationService::clearCache();
        app()->setLocale('lt');

        expect(NavigationService::getFooterNavigationForPublic())->toHaveCount(NavigationService::FOOTER_MAX_COLUMNS);
    });

    test('clearCache also clears the footer cache', function (): void {
        NavigationService::clearCache();
        app()->setLocale('lt');
        NavigationService::getFooterNavigationForPublic();

        expect(Cache::has('navigation:footer:lt'))->toBeTrue();

        NavigationService::clearCache();

        expect(Cache::has('navigation:footer:lt'))->toBeFalse();
    });

    test('getFooterTreeForAdmin returns only footer-location roots, inactive included', function (): void {
        $footerRoot = Navigation::factory()->create([
            'parent_id' => 0,
            'lang' => 'lt',
            'is_active' => false,
            'extra_attributes' => ['location' => 'footer', 'type' => 'category-link'],
        ]);

        $tree = NavigationService::getFooterTreeForAdmin('lt');

        expect(collect($tree)->pluck('id'))->toContain($footerRoot->id)
            ->and(collect($tree)->pluck('id'))->not->toContain($this->rootNav->id);
    });
});
