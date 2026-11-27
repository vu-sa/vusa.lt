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

        asUserWithInertia($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexPermission')
                ->has('permissions')
            );
    });

    test('permission index sends every permission for the browser to search', function (): void {
        Permission::factory()->count(3)->create();

        asUserWithInertia($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions', Permission::query()->count())
            );
    });
});

describe('permission data integrity', function (): void {
    test('permissions are displayed with correct structure', function (): void {
        $permission = Permission::factory()->create([
            'name' => 'test.permission',
            'guard_name' => 'web',
        ]);

        asUserWithInertia($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions', fn ($rows) => collect($rows)->firstWhere('id', $permission->id)['name'] === 'test.permission')
            );
    });

    test('permission index handles empty state', function (): void {
        // Clear any existing permissions
        Permission::query()->delete();

        asUserWithInertia($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions', 0)
            );
    });
});

describe('permission filtering and search', function (): void {
    test('permission index ignores paging and sends the whole list', function (): void {
        Permission::factory()->count(3)->create();

        asUserWithInertia($this->admin)
            ->get(route('permissions.index', ['page' => 2]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('permissions', Permission::query()->count())
            );
    });

    test('permission list is sorted consistently', function (): void {
        Permission::factory()->create(['name' => 'zebra.permission']);
        Permission::factory()->create(['name' => 'alpha.permission']);

        asUserWithInertia($this->admin)
            ->get(route('permissions.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions', fn ($rows) => collect($rows)->pluck('name')
                    ->filter(fn (string $name) => in_array($name, ['alpha.permission', 'zebra.permission'], true))
                    ->values()->all() === ['alpha.permission', 'zebra.permission'])
            );
    });
});
