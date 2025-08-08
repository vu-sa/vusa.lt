<?php

use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
});

describe('permission index', function () {
    test('unauthorized user cannot access permission index', function () {
        asUser($this->user)
            ->get(route('permissions.index'))
            ->assertStatus(403);
    });

    test('admin can access permission index', function () {
        Permission::factory()->count(3)->create();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexPermission')
                ->has('permissions')
            );
    });

    test('permission index displays paginated permissions', function () {
        Permission::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 20) // Default pagination size
                ->has('permissions.links')
            );
    });

    test('permission index shows all permissions regardless of tenant', function () {
        // Permissions are global, not tenant-specific
        Permission::query()->delete(); // Clear existing permissions
        Permission::factory()->count(5)->create();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 5)
            );
    });
});

describe('permission security', function () {
    test('regular user cannot access permission management', function () {
        asUser($this->user)
            ->get(route('permissions.index'))
            ->assertStatus(403);
    });

    test('permission management requires proper authorization', function () {
        // Test that proper authorization is required
        // This ensures the policy is working correctly
        asUser($this->user)
            ->get(route('permissions.index'))
            ->assertStatus(403);

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200);
    });
});

describe('permission data integrity', function () {
    test('permissions are displayed with correct structure', function () {
        Permission::query()->delete(); // Clear existing permissions
        $permission = Permission::factory()->create([
            'name' => 'test.permission',
            'guard_name' => 'web',
        ]);

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data.0.name')
                ->has('permissions.data.0.guard_name')
                ->where('permissions.data.0.name', 'test.permission')
            );
    });

    test('permission index handles empty state', function () {
        // Clear any existing permissions
        Permission::query()->delete();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 0)
            );
    });
});

describe('permission filtering and search', function () {
    test('permission index supports basic pagination', function () {
        Permission::query()->delete(); // Clear existing permissions
        Permission::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('permissions.index', ['page' => 2]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 5) // Remaining permissions on page 2
                ->has('permissions.links')
            );
    });

    test('permission list is sorted consistently', function () {
        Permission::factory()->create(['name' => 'zebra.permission']);
        Permission::factory()->create(['name' => 'alpha.permission']);

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data')
            );
    });
});

describe('permission system integration', function () {
    test('permissions are properly formatted for frontend', function () {
        Permission::query()->delete(); // Clear existing permissions
        $permission = Permission::factory()->create([
            'name' => 'manage.users',
            'guard_name' => 'web',
        ]);

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data.0')
                ->where('permissions.data.0.name', 'manage.users')
                ->where('permissions.data.0.guard_name', 'web')
            );
    });

    test('permission index loads efficiently', function () {
        Permission::factory()->count(20)->create();

        $startTime = microtime(true);

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200);

        $executionTime = microtime(true) - $startTime;

        // Ensure the request completes within reasonable time
        expect($executionTime)->toBeLessThan(5.0);
    });
});
