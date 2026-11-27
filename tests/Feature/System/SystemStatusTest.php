<?php

use App\Enums\SystemMaintenanceAction;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Activity;
use App\Models\Tenant;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->user->assignRole(config('permission.super_admin_role_name'));
});

describe('SystemStatus: Authentication & Authorization', function (): void {
    test('requires authentication to access system status page', function (): void {
        $this->get('/mano/system-status')
            ->assertRedirect('/login');
    });

    test('system status page returns Inertia response', function (): void {
        asUserWithInertia($this->user)->get('/mano/system-status')
            ->assertOk()
            ->assertHeader('X-Inertia', 'true');
    });
});

describe('SystemStatus: Page Content', function (): void {
    test('system status page includes required status data', function (): void {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        expect($props)->toHaveKeys(['status', 'lastUpdated', 'deviceMetrics'])
            ->and($props['status'])->toHaveKeys(['redis', 'database', 'cache', 'integrations', 'system'])
            ->and($props['deviceMetrics'])->toHaveKeys(['records', 'summary']);
    });

    test('redis status includes essential information', function (): void {
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

    test('database status includes connection information', function (): void {
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

    test('cache status reflects current cache driver', function (): void {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $cache = $props['status']['cache'];

        expect($cache)->toHaveKeys(['status', 'working', 'driver'])
            ->and($cache['working'])->toBeTrue();
    });

    test('integrations status includes all configured integrations', function (): void {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $integrations = $props['status']['integrations'];

        expect($integrations)->toHaveKeys(['microsoft', 'sharepoint', 'mail', 'scout']);

        foreach (['microsoft', 'sharepoint', 'mail', 'scout'] as $integration) {
            expect($integrations[$integration])->toHaveKeys(['configured', 'status']);
        }
    });

    test('system information includes essential server details', function (): void {
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

describe('SystemStatus: Auto-refresh with Polling', function (): void {
    test('status page includes auto-refresh data for polling', function (): void {
        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        // Ensure data structure supports polling
        expect($props)->toHaveKeys(['status', 'lastUpdated']);
        expect($props['lastUpdated'])->not()->toBeEmpty();
    });

});

describe('SystemStatus: Security', function (): void {

    test('integration configuration reports credential presence without returning credential values', function (): void {
        config([
            'services.microsoft.client_secret' => 'test-microsoft-secret-marker',
            'filesystems.sharepoint.client_secret' => 'test-sharepoint-secret-marker',
        ]);

        $response = asUser($this->user)->get(route('systemStatus'))->assertOk();
        $integrations = $response->getOriginalContent()->getData()['page']['props']['status']['integrations'];

        expect($integrations['microsoft']['client_secret_set'])->toBeTrue();
        expect($integrations['sharepoint']['client_secret_set'])->toBeTrue();
        expect(json_encode($integrations))->not->toContain('test-microsoft-secret-marker')
            ->not->toContain('test-sharepoint-secret-marker');
    });

});

describe('SystemStatus: Error Handling', function (): void {
    test('handles redis connection failures gracefully', function (): void {
        // Mock Redis failure scenario by temporarily changing config
        config(['database.redis.default.host' => 'invalid-host']);

        $response = asUser($this->user)->get('/mano/system-status');

        $response->assertStatus(200);
        $props = $response->getOriginalContent()->getData()['page']['props'];

        expect($props['status']['redis']['status'])->toBe('error')
            ->and($props['status']['redis'])->toHaveKey('error');
    });

    test('continues to show other services when one fails', function (): void {
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

describe('SystemStatus: Maintenance actions', function (): void {
    test('only super admins are offered or may run maintenance actions', function (): void {
        $admin = makeUser($this->tenant);

        asUser($admin)->post(route('systemStatus.maintenance'), ['action' => 'refresh-public-content'])
            ->assertForbidden();

        $props = asUser($this->user)->get('/mano/system-status')
            ->getOriginalContent()->getData()['page']['props'];

        expect(collect($props['maintenanceActions'])->pluck('action')->all())
            ->toBe(array_column(SystemMaintenanceAction::cases(), 'value'));
    });

    test('rejects an action outside the allowlist', function (): void {
        asUser($this->user)->post(route('systemStatus.maintenance'), ['action' => 'migrate:fresh'])
            ->assertSessionHasErrors('action');
    });

    test('refreshing public content clears tagged content and shared Inertia caches', function (): void {
        Cache::tags(['homepage', 'tenant_1'])->put('home', 'stale');
        Cache::tags(['navigation', 'locale_lt'])->put('nav', 'stale');
        Cache::forever(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY, 'stale');
        Cache::forever('unrelated', 'kept');

        asUser($this->user)->post(route('systemStatus.maintenance'), ['action' => 'refresh-public-content'])
            ->assertSessionHas('success');

        expect(Cache::tags(['homepage', 'tenant_1'])->get('home'))->toBeNull()
            ->and(Cache::tags(['navigation', 'locale_lt'])->get('nav'))->toBeNull()
            ->and(Cache::get(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY))->toBeNull()
            ->and(Cache::get('unrelated'))->toBe('kept')
            ->and(Activity::query()->where('event', 'system_maintenance')->latest('id')->first())->causer_id->toBe($this->user->id)->properties->get('action')
            ->toBe('refresh-public-content');
    });

    test('clearing the application cache keeps the scheduler heartbeat', function (): void {
        Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, '2026-09-26T10:00:00+00:00');
        Cache::forever('unrelated', 'gone');

        asUser($this->user)->post(route('systemStatus.maintenance'), ['action' => 'clear-application-cache'])
            ->assertSessionHas('success');

        expect(Cache::get('unrelated'))->toBeNull()
            ->and(Cache::get(SystemMonitorService::HEARTBEAT_CACHE_KEY))->toBe('2026-09-26T10:00:00+00:00');
    });

    test('long-running actions are queued instead of run in the request', function (): void {
        Queue::fake();

        asUser($this->user)->post(route('systemStatus.maintenance'), ['action' => 'reindex-search'])
            ->assertSessionHas('success', __('messages.system_maintenance.queued'));

        Queue::assertPushed(QueuedCommand::class, fn (QueuedCommand $job) => $job->displayName() === 'search:reindex');
    });
});

test('a member without role-reading permission cannot open system status', function (): void {
    asUser(makeUser($this->tenant))->get(route('systemStatus'))->assertForbidden();
});

test('a non-super-admin role reader sees the snapshot but cannot run maintenance', function (): void {
    $reader = makeUser($this->tenant);
    $reader->givePermissionTo('roles.read.*');

    asUser($reader)->get(route('systemStatus'))->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('status')->has('deviceMetrics')->has('maintenanceActions', 0));
    asUser($reader)->post(route('systemStatus.maintenance'), ['action' => 'refresh-public-content'])->assertForbidden();
});
