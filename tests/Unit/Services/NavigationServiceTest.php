<?php

use App\Models\Navigation;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
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

describe('NavigationService caching', function () {
    test('getNavigationForPublic returns cached result on second call', function () {
        // Clear any existing cache
        NavigationService::clearCache();

        // First call should query the database
        $result1 = NavigationService::getNavigationForPublic();

        // Second call should use cache - verify by checking the result is identical
        $result2 = NavigationService::getNavigationForPublic();

        expect($result1)->toEqual($result2);
        expect($result1)->toBeArray();
        expect($result1)->not->toBeEmpty();
    });

    test('cache is keyed by locale', function () {
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

    test('clearCache clears all locale caches', function () {
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

    test('cache is invalidated when navigation is saved', function () {
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

    test('cache is invalidated when navigation is deleted', function () {
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

describe('NavigationService output structure', function () {
    test('returns correct structure with links and columns', function () {
        NavigationService::clearCache();
        app()->setLocale('lt');

        $result = NavigationService::getNavigationForPublic();

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();

        // Root element should have 'links' and 'cols' keys
        $rootElement = $result[0];
        expect($rootElement)->toHaveKey('name');
        expect($rootElement)->toHaveKey('links');
        expect($rootElement)->toHaveKey('cols');
    });

    test('children are organized into correct columns', function () {
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
});
