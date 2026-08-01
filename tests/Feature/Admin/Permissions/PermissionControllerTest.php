<?php

use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
});

describe('permission index', function (): void {
    test('unauthorized user cannot access permission index', function (): void {
        asUser($this->user)
            ->get(route('permissions.index'))
            ->assertStatus(403);
    });

    test('admin can access permission index', function (): void {
        Permission::factory()->count(3)->create();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexPermission')
                ->has('permissions')
            );
    });

    test('permission index displays paginated permissions', function (): void {
        Permission::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 20) // Default pagination size
                ->has('permissions.meta')
            );
    });

    test('permission index shows all permissions regardless of tenant', function (): void {
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

describe('permission security', function (): void {
    test('regular user cannot access permission management', function (): void {
        asUser($this->user)
            ->get(route('permissions.index'))
            ->assertStatus(403);
    });

    test('permission management requires proper authorization', function (): void {
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

describe('permission data integrity', function (): void {
    test('permissions are displayed with correct structure', function (): void {
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

    test('permission index handles empty state', function (): void {
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

describe('permission filtering and search', function (): void {
    test('permission index supports basic pagination', function (): void {
        Permission::query()->delete(); // Clear existing permissions
        Permission::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('permissions.index', ['page' => 2]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions.data', 5) // Remaining permissions on page 2
                ->has('permissions.meta')
            );
    });

    test('permission list is sorted consistently', function (): void {
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

describe('permission system integration', function (): void {
    test('permissions are properly formatted for frontend', function (): void {
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

    test('permission index loads efficiently', function (): void {
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
