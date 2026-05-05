<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

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

describe('cache restores permissableDuties state', function () {
    test('permissableDuties is correctly restored from cache on repeated checks', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);

        // First check — populates permissableDuties via duty loop
        $result1 = $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($result1)->toBeTrue();

        $dutiesAfterFirst = $this->authorizer->getPermissableDuties();
        expect($dutiesAfterFirst)->toHaveCount(1);

        // Second check — should restore permissableDuties from cache
        $result2 = $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($result2)->toBeTrue();

        $dutiesAfterSecond = $this->authorizer->getPermissableDuties();
        expect($dutiesAfterSecond)->toHaveCount(1);
        expect($dutiesAfterSecond->first()->id)->toBe($dutiesAfterFirst->first()->id);
    });

    test('permissableDuties is not polluted by unrelated permission checks', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);

        // Check a permission the user HAS — sets permissableDuties
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(1);

        // Check a permission the user does NOT have — should clear permissableDuties
        $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(0);

        // Re-check the first permission — cache should restore the correct duties
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(1);
    });

    test('isAllScope is correctly restored from cache', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $normalUser = makeUser($this->tenant);

        // Super admin check sets isAllScope = true
        $this->authorizer->forUser($superAdmin);
        $this->authorizer->checkAllRoleables('users.read.padalinys');

        // Switch to normal user — clears cache and isAllScope
        $this->authorizer->forUser($normalUser);
        $this->authorizer->checkAllRoleables('users.read.padalinys');

        // Switch back to super admin — cache should restore isAllScope = true
        $this->authorizer->forUser($superAdmin);
        $this->authorizer->checkAllRoleables('users.read.padalinys');

        $tenants = $this->authorizer->getTenants();
        expect($tenants->count())->toBeGreaterThan(1);
    });
});

describe('commonChecker authorization flow with caching', function () {
    test('user with padalinys permission can update models in same tenant', function () {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $targetUser = makeUser($this->tenant);

        // Simulate the commonChecker flow: check .all, .own, .padalinys sequentially
        $this->authorizer->forUser($admin);

        $allResult = $this->authorizer->checkAllRoleables('users.update.all');
        expect($allResult)->toBeFalse();

        $ownResult = $this->authorizer->checkAllRoleables('users.update.own');
        expect($ownResult)->toBeFalse();

        $padalinysResult = $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($padalinysResult)->toBeTrue();

        // permissableDuties should contain the admin's duty
        $permissableDuties = $this->authorizer->getPermissableDuties();
        expect($permissableDuties)->toHaveCount(1);

        // The duty's institution should be in the same tenant
        $permissableTenants = $admin->tenants()
            ->whereIn('duties.id', $permissableDuties->pluck('id'))
            ->get();

        $targetTenants = $targetUser->load('tenants')->tenants;

        expect($permissableTenants->intersect($targetTenants))->not->toBeEmpty();
    });

    test('repeated commonChecker flow returns consistent results from cache', function () {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($admin);

        // First pass (simulating first HTTP request's policy check)
        $this->authorizer->checkAllRoleables('users.update.all');
        $this->authorizer->checkAllRoleables('users.update.own');
        $this->authorizer->checkAllRoleables('users.update.padalinys');

        $dutiesFirstPass = $this->authorizer->getPermissableDuties();
        expect($dutiesFirstPass)->toHaveCount(1);

        // Second pass (simulating second HTTP request — same singleton)
        // All checks should come from cache
        $this->authorizer->checkAllRoleables('users.update.all');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(0);

        $this->authorizer->checkAllRoleables('users.update.own');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(0);

        $this->authorizer->checkAllRoleables('users.update.padalinys');
        $dutiesSecondPass = $this->authorizer->getPermissableDuties();

        expect($dutiesSecondPass)->toHaveCount(1);
        expect($dutiesSecondPass->first()->id)->toBe($dutiesFirstPass->first()->id);
    });
});

describe('getTenants with cached permission state', function () {
    test('getTenants returns correct tenants after cached padalinys check', function () {
        $admin = makeUser($this->tenant);
        $admin->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($admin);

        // First call — populates cache
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        $tenants1 = $this->authorizer->getTenants('users.update.padalinys');

        // Second call — uses cache
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        $tenants2 = $this->authorizer->getTenants('users.update.padalinys');

        expect($tenants1->pluck('id')->sort()->values())
            ->toEqual($tenants2->pluck('id')->sort()->values());
        expect($tenants1)->toHaveCount(1);
        expect($tenants1->first()->id)->toBe($this->tenant->id);
    });
});

describe('cache invalidation via resetCache', function () {
    test('resetCache clears in-memory permission cache and forces re-evaluation', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);

        // Populate cache
        expect($this->authorizer->checkAllRoleables('users.update.padalinys'))->toBeTrue();
        expect($this->authorizer->getPermissableDuties())->toHaveCount(1);

        // Reset cache — clears in-memory state and unsets user reference
        $this->authorizer->resetCache($user);

        // Remove the role after cache reset
        $user->duties()->first()->removeRole($this->coordinatorRole);
        $user->refresh();

        // forUser must re-accept the refreshed model since user was unset
        $result = $this->authorizer->forUser($user)->checkAllRoleables('users.update.padalinys');
        expect($result)->toBeFalse();
    });

    test('resetCache clears in-memory cache when called with user ID', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(1);

        // Reset by ID instead of model
        $this->authorizer->resetCache($user->id);

        // In-memory state should be cleared
        expect($this->authorizer->getPermissableDuties())->toHaveCount(0);
    });

    test('Permission facade resetCache invalidates authorizer cache', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);
        $this->authorizer->checkAllRoleables('users.update.padalinys');
        expect($this->authorizer->getPermissableDuties())->toHaveCount(1);

        // Call through the facade (same path as observers)
        PermissionFacade::resetCache($user);

        expect($this->authorizer->getPermissableDuties())->toHaveCount(0);
    });

    test('resetCache clears Redis duties cache', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);
        $this->authorizer->checkAllRoleables('users.update.padalinys');

        // Duties should be cached in Redis
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        $this->authorizer->resetCache($user);

        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });
});

describe('observer-triggered cache invalidation', function () {
    test('user model update triggers cache invalidation via observer', function () {
        $user = makeUser($this->tenant);
        $user->duties()->first()->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);
        $this->authorizer->checkAllRoleables('users.update.padalinys');

        // Verify cache is populated
        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        // Update user — triggers UserPermissionObserver::updated()
        $user->update(['name' => 'New Name']);

        // Observer calls resetCache → Redis duties cache should be cleared
        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });

    test('duty model update triggers cache invalidation for associated users', function () {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();
        $duty->assignRole($this->coordinatorRole);

        $this->authorizer->forUser($user);
        $this->authorizer->checkAllRoleables('users.update.padalinys');

        expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

        // Update the duty — triggers UserPermissionObserver::updatedDuty()
        $duty->update(['name' => ['lt' => 'Updated Duty Name', 'en' => 'Updated Duty Name']]);

        // Observer should clear cache for all users with this duty
        expect(Cache::has("auth:duties:{$user->id}"))->toBeFalse();
    });

    test('role assignment and removal require explicit cache reset', function () {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();

        $this->authorizer->forUser($user);
        expect($this->authorizer->checkAllRoleables('users.update.padalinys'))->toBeFalse();

        // assignRole/removeRole don't trigger Eloquent model events
        // They fire Spatie events (RoleAttached/RoleDetached) which are not listened to
        // So explicit resetCache is needed after role changes on duties
        $duty->assignRole($this->coordinatorRole);

        // Cache still has stale "false" result
        expect($this->authorizer->checkAllRoleables('users.update.padalinys'))->toBeFalse();

        // After explicit reset + re-init, the new role is picked up
        PermissionFacade::resetCache($user);
        $user->refresh();
        expect($this->authorizer->forUser($user)->checkAllRoleables('users.update.padalinys'))->toBeTrue();
    });
});

describe('cache TTL', function () {
    test('loadDuties cache TTL is at most 1 hour', function () {
        $reflection = new ReflectionClass(ModelAuthorizer::class);
        $ttl = $reflection->getConstant('CACHE_TTL');

        expect($ttl)->toBeLessThanOrEqual(3600);
    });
});
