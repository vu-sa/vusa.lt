<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    // Create permissions for testing
    $permissions = [
        'users.read.padalinys',
        'users.update.padalinys',
        'users.read.all',
        'users.update.all',
        'users.read.own',
        'users.update.own',
        'news.read.padalinys',
    ];

    foreach ($permissions as $permission) {
        if (! Permission::where('name', $permission)->exists()) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    $this->coordinatorRole = Role::firstOrCreate(
        ['name' => 'Cache Test Coordinator', 'guard_name' => 'web']
    );
    $this->coordinatorRole->syncPermissions([
        'users.read.padalinys',
        'users.update.padalinys',
    ]);

    $this->authorizer = app(ModelAuthorizer::class);
});

describe('memoized resolutions stay independent', function (): void {
    test('the granting duties are the same on a repeated resolution', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $first = $this->authorizer->duties($user, 'users.update.padalinys');
        expect($first)->toHaveCount(1);

        $second = $this->authorizer->duties($user, 'users.update.padalinys');
        expect($second)->toHaveCount(1)
            ->and($second->first()->id)->toBe($first->first()->id);
    });

    test('an unrelated permission does not disturb a granted one', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        expect($this->authorizer->duties($user, 'users.update.padalinys'))->toHaveCount(1);

        // A permission the user does NOT have resolves to nothing...
        expect($this->authorizer->duties($user, 'news.read.padalinys'))->toBeEmpty();

        // ...and leaves the granted one untouched.
        expect($this->authorizer->duties($user, 'users.update.padalinys'))->toHaveCount(1);
    });

    test('a super admins all-scope result is not affected by resolving another user', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $normalUser = makeUser($this->tenant);

        expect($this->authorizer->tenants($superAdmin, 'users.read.padalinys')->count())->toBeGreaterThan(1);
        expect($this->authorizer->tenants($normalUser, 'users.read.padalinys'))->toBeEmpty();
        expect($this->authorizer->tenants($superAdmin, 'users.read.padalinys')->count())->toBeGreaterThan(1);
    });
});

describe('commonChecker authorization flow', function (): void {
    test('user with padalinys permission can update models in same tenant', function (): void {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $targetUser = makeUser($this->tenant);

        // Simulate the commonChecker flow: check .all, .own, .padalinys sequentially
        expect($this->authorizer->allows($admin, 'users.update.all'))->toBeFalse()
            ->and($this->authorizer->allows($admin, 'users.update.own'))->toBeFalse()
            ->and($this->authorizer->allows($admin, 'users.update.padalinys'))->toBeTrue();

        $permissableDuties = $this->authorizer->duties($admin, 'users.update.padalinys');
        expect($permissableDuties)->toHaveCount(1);

        // The duty's institution should be in the same tenant
        $permissableTenants = $admin->tenants()
            ->whereIn('duties.id', $permissableDuties->pluck('id'))
            ->get();

        $targetTenants = $targetUser->load('tenants')->tenants;

        expect($permissableTenants->intersect($targetTenants))->not->toBeEmpty();
    });

    test('repeated commonChecker flow returns consistent results from cache', function (): void {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        // First pass (simulating a policy check)
        $this->authorizer->allows($admin, 'users.update.all');
        $this->authorizer->allows($admin, 'users.update.own');
        $dutiesFirstPass = $this->authorizer->duties($admin, 'users.update.padalinys');
        expect($dutiesFirstPass)->toHaveCount(1);

        // Second pass — every resolution comes from the memo, unchanged
        expect($this->authorizer->duties($admin, 'users.update.all'))->toBeEmpty()
            ->and($this->authorizer->duties($admin, 'users.update.own'))->toBeEmpty();

        $dutiesSecondPass = $this->authorizer->duties($admin, 'users.update.padalinys');

        expect($dutiesSecondPass)->toHaveCount(1)
            ->and($dutiesSecondPass->first()->id)->toBe($dutiesFirstPass->first()->id);
    });
});

describe('tenant resolution', function (): void {
    test('a padalinys permission resolves to the actors own tenant, repeatably', function (): void {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $tenants1 = $this->authorizer->tenants($admin, 'users.update.padalinys');
        $tenants2 = $this->authorizer->tenants($admin, 'users.update.padalinys');

        // Same instance proves the resolution was memoized, not recomputed.
        expect($tenants2)->toBe($tenants1)
            ->and($tenants1)->toHaveCount(1)
            ->and($tenants1->first()->id)->toBe($this->tenant->id);
    });

    test('a super admin resolves to every tenant, memoized', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $first = $this->authorizer->tenants($superAdmin, 'users.read.padalinys');
        $second = $this->authorizer->tenants($superAdmin, 'users.read.padalinys');

        expect($second)->toBe($first)
            ->and($first->count())->toBe(Tenant::count());
    });

    test('the scope carries tenants, duties and all-scope together', function (): void {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $scope = $this->authorizer->scope($admin, 'users.update.padalinys');

        expect($scope->isAllScope)->toBeFalse()
            ->and($scope->duties)->toHaveCount(1)
            ->and($scope->tenants)->toHaveCount(1);
    });
});

describe('cache invalidation via resetCache', function (): void {
    test('resetCache clears in-memory permission cache and forces re-evaluation', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        // Populate cache
        expect($this->authorizer->allows($user, 'users.update.padalinys'))->toBeTrue()
            ->and($this->authorizer->duties($user, 'users.update.padalinys'))->toHaveCount(1);

        $this->authorizer->resetCache($user);

        // Remove the role after cache reset
        $user->duties()->first()->removeRole($this->coordinatorRole);
        $user->refresh();

        expect($this->authorizer->allows($user, 'users.update.padalinys'))->toBeFalse();
    });

    test('resetCache clears in-memory cache when called with user ID', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $before = $this->authorizer->scope($user, 'users.update.padalinys');

        // Reset by ID instead of model
        $this->authorizer->resetCache($user->id);

        expect($this->authorizer->scope($user, 'users.update.padalinys'))->not->toBe($before);
    });

    test('Permission facade resetCache invalidates authorizer cache', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->allows($user, 'users.update.padalinys');
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        // Call through the facade (same path as observers)
        PermissionFacade::resetCache($user);

        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });

    test('resetCache clears Redis duties cache', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->allows($user, 'users.update.padalinys');

        // Duties should be cached in Redis
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        $this->authorizer->resetCache($user);

        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });
});

describe('observer-triggered cache invalidation', function (): void {
    test('user model update triggers cache invalidation via observer', function (): void {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->allows($user, 'users.update.padalinys');

        // Verify cache is populated
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        // Update user — triggers UserPermissionObserver::updated()
        $user->update(['name' => 'New Name']);

        // Observer calls resetCache → Redis duties cache should be cleared
        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });

    test('duty model update triggers cache invalidation for associated users', function (): void {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();
        $duty->assignRole($this->coordinatorRole);

        $this->authorizer->allows($user, 'users.update.padalinys');

        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        // Update the duty — triggers UserPermissionObserver::updatedDuty()
        $duty->update(['name' => ['lt' => 'Updated Duty Name', 'en' => 'Updated Duty Name']]);

        // Observer should clear cache for all users with this duty
        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });

    test('role assignment and removal require explicit cache reset', function (): void {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();

        expect($this->authorizer->allows($user, 'users.update.padalinys'))->toBeFalse();

        // assignRole/removeRole don't trigger Eloquent model events
        // They fire Spatie events (RoleAttached/RoleDetached) which are not listened to
        // So explicit resetCache is needed after role changes on duties
        $duty->assignRole($this->coordinatorRole);

        // Cache still has stale "false" result
        expect($this->authorizer->allows($user, 'users.update.padalinys'))->toBeFalse();

        PermissionFacade::resetCache($user);
        $user->refresh();
        expect($this->authorizer->allows($user, 'users.update.padalinys'))->toBeTrue();
    });
});

describe('cache TTL', function (): void {
    test('loadDuties cache TTL is at most 1 hour', function (): void {
        $reflection = new ReflectionClass(ModelAuthorizer::class);
        $ttl = $reflection->getConstant('CACHE_TTL');

        expect($ttl)->toBeLessThanOrEqual(3600);
    });
});
