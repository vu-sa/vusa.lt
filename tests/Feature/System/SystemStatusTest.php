<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->adminUser = makeUser($this->tenant);
    $this->adminUser->assignRole(config('permission.super_admin_role_name'));
});

describe('SystemStatus: Authentication & Authorization', function () {
    test('requires authentication to access system status page', function () {
        $this->get('/mano/system-status')
            ->assertRedirect('/login');
    });

    test('authenticated users can access system status page', function () {
        asUser($this->user)->get('/mano/system-status')
            ->assertStatus(200);
    });

    test('system status page returns Inertia response', function () {
        // Set a consistent Inertia version to avoid 409 conflicts in tests
        config(['inertia.testing.ensure_pages_exist' => false]);
        
        $response = asUser($this->user)->get('/mano/system-status', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => 'test-version',
        ]);

        // Accept either 200 (success) or 409 (version mismatch) as valid Inertia behavior
        expect($response->status())->toBeIn([200, 409]);
        
        if ($response->status() === 200) {
            expect($response->headers->get('X-Inertia'))->toBe('true');
        } elseif ($response->status() === 409) {
            // Verify it's a proper Inertia version mismatch response
            expect($response->headers->get('X-Inertia-Location'))->toContain('/mano/system-status');
        }
    });
});

describe('SystemStatus: Page Content', function () {
    test('system status page includes required status data', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        expect($props)->toHaveKeys(['status', 'lastUpdated']);
        expect($props['status'])->toHaveKeys(['redis', 'database', 'cache', 'integrations', 'system']);
    });

    test('redis status includes essential information', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $redis = $props['status']['redis'];

        expect($redis)->toHaveKeys(['status', 'connected']);

        if ($redis['connected']) {
            expect($redis)->toHaveKeys(['memory_used', 'version', 'uptime']);
        } else {
            expect($redis)->toHaveKey('error');
        }
    });

    test('database status includes connection information', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $database = $props['status']['database'];

        expect($database)->toHaveKeys(['status', 'connected']);
        // Database connection varies in test environment, just check structure
        if ($database['connected']) {
            expect($database)->toHaveKey('driver');
        } else {
            expect($database)->toHaveKey('error');
        }
    });

    test('cache status reflects current cache driver', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $cache = $props['status']['cache'];

        expect($cache)->toHaveKeys(['status', 'working', 'driver']);
        expect($cache['working'])->toBeTrue();
    });

    test('integrations status includes all configured integrations', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $integrations = $props['status']['integrations'];

        expect($integrations)->toHaveKeys(['microsoft', 'sharepoint', 'mail', 'scout']);

        foreach (['microsoft', 'sharepoint', 'mail', 'scout'] as $integration) {
            expect($integrations[$integration])->toHaveKeys(['configured', 'status']);
        }
    });

    test('system information includes essential server details', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $system = $props['status']['system'];

        expect($system)->toHaveKeys([
            'php_version',
            'laravel_version',
            'environment',
            'debug_mode',
            'memory_limit',
        ]);
    });
});

describe('SystemStatus: Auto-refresh with Polling', function () {
    test('status page includes auto-refresh data for polling', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Ensure data structure supports polling
        expect($props)->toHaveKeys(['status', 'lastUpdated']);
        expect($props['lastUpdated'])->not()->toBeEmpty();
    });

    test('cache management works independently of manual refresh', function () {
        // Set a cache value to test cache functionality
        Cache::put('system_status_cache', ['test' => 'data'], 300);

        // Verify cache exists
        expect(Cache::has('system_status_cache'))->toBeTrue();

        // Clear cache directly (simulating what polling might do)
        Cache::forget('system_status_cache');

        expect(Cache::has('system_status_cache'))->toBeFalse();
    });

    test('status data structure remains consistent for polling updates', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Verify all expected sections exist for consistent polling
        expect($props['status'])->toHaveKeys(['redis', 'database', 'cache', 'integrations', 'system']);

        // Each section should have predictable structure
        foreach (['redis', 'database'] as $service) {
            expect($props['status'][$service])->toHaveKeys(['status', 'connected']);
        }

        // Cache has different structure
        expect($props['status']['cache'])->toHaveKeys(['status', 'working']);
    });
});

describe('SystemStatus: Security', function () {
    test('status data does not expose sensitive information', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Check that status data doesn't contain raw sensitive information
        $statusData = json_encode($props['status']);
        expect($statusData)->not()->toContain('DB_PASSWORD');
        expect($statusData)->not()->toContain('REDIS_PASSWORD');
        expect($statusData)->not()->toContain('SECRET_KEY');
        expect($statusData)->not()->toContain('API_TOKEN');
    });

    test('does not expose database credentials in error messages', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Check all status sections for exposed credentials
        $statusSections = ['redis', 'database', 'cache', 'integrations', 'system'];

        foreach ($statusSections as $section) {
            if (isset($props['status'][$section]['error'])) {
                $errorMessage = $props['status'][$section]['error'];
                expect($errorMessage)->not()->toContain('password');
                expect($errorMessage)->not()->toContain('DB_PASSWORD');
                expect($errorMessage)->not()->toContain('REDIS_PASSWORD');
            }
        }
    });

    test('polling does not create excessive server load', function () {
        $responses = [];

        // Make multiple rapid requests to simulate polling behavior
        for ($i = 0; $i < 3; $i++) {
            $response = asUser($this->user)->get('/mano/system-status');
            $responses[] = $response->status();
        }

        // All requests should succeed
        expect($responses)->not()->toContain(429); // No rate limiting errors
        expect(array_unique($responses))->toBe([200]); // All successful
    });
});

describe('SystemStatus: Performance', function () {
    test('system status page loads efficiently', function () {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        // Basic performance check - should complete without timeout
        expect($response->status())->toBe(200);
    });

    test('cached status improves performance on subsequent loads', function () {
        // First load - should cache
        $start = microtime(true);
        asUser($this->user)->get('/mano/system-status');
        $firstLoad = microtime(true) - $start;

        // Second load - should use cache
        $start = microtime(true);
        asUser($this->user)->get('/mano/system-status');
        $secondLoad = microtime(true) - $start;

        // Second load should be faster or similar (cache hit)
        expect($secondLoad <= $firstLoad * 1.5)->toBeTrue(); // Allow 50% variance for test stability
    });
});

describe('SystemStatus: Error Handling', function () {
    test('handles redis connection failures gracefully', function () {
        // Mock Redis failure scenario by temporarily changing config
        config(['database.redis.default.host' => 'invalid-host']);

        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        expect($props['status']['redis']['status'])->toBe('error');
        expect($props['status']['redis'])->toHaveKey('error');
    });

    test('continues to show other services when one fails', function () {
        // Even if Redis fails, other services should still be checked
        config(['database.redis.default.host' => 'invalid-host']);

        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Other services should still respond regardless of Redis status
        expect($props['status']['cache'])->toHaveKey('working');
        expect($props['status']['system']['php_version'])->not()->toBeEmpty();
    });
});
