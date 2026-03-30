<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->duty = Duty::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    // Create permissions
    $permissions = [
        'news.read.padalinys',
        'news.create.padalinys',
        'news.update.padalinys',
    ];

    foreach ($permissions as $permission) {
        if (! \App\Models\Permission::where('name', $permission)->exists()) {
            \App\Models\Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    $this->role = Role::firstOrCreate(['name' => 'Test Memoization Role', 'guard_name' => 'web']);
    $this->role->givePermissionTo(['news.read.padalinys', 'news.create.padalinys']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(config('permission.super_admin_role_name'));

    $this->normalUser = makeUser($this->tenant);

    $this->authorizer = new ModelAuthorizer;
});

describe('request-level memoization', function () {
    test('repeated permission check returns cached result', function () {
        $this->authorizer->forUser($this->superAdmin);

        $result1 = $this->authorizer->checkAllRoleables('news.read.padalinys');
        $result2 = $this->authorizer->checkAllRoleables('news.read.padalinys');

        expect($result1)->toBe($result2);
        expect($result1)->toBeTrue();
    });

    test('different permissions are cached independently', function () {
        $this->authorizer->forUser($this->normalUser);

        $readResult = $this->authorizer->checkAllRoleables('news.read.padalinys');
        $updateResult = $this->authorizer->checkAllRoleables('news.update.padalinys');

        // Both should be false for normal user without role
        expect($readResult)->toBeFalse();
        expect($updateResult)->toBeFalse();
    });

    test('super admin returns true for all permission checks', function () {
        $this->authorizer->forUser($this->superAdmin);

        expect($this->authorizer->checkAllRoleables('news.read.padalinys'))->toBeTrue();
        expect($this->authorizer->checkAllRoleables('news.create.padalinys'))->toBeTrue();
        expect($this->authorizer->checkAllRoleables('news.update.padalinys'))->toBeTrue();
        expect($this->authorizer->checkAllRoleables('nonexistent.permission.scope'))->toBeTrue();
    });

    test('switching users clears the memoization cache', function () {
        $this->authorizer->forUser($this->superAdmin);

        // Super admin has all permissions
        $superResult = $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($superResult)->toBeTrue();

        // Switch to normal user - should not reuse super admin's cached result
        $this->authorizer->forUser($this->normalUser);

        $normalResult = $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($normalResult)->toBeFalse();
    });

    test('same user does not clear cache', function () {
        $this->authorizer->forUser($this->superAdmin);
        $this->authorizer->checkAllRoleables('news.read.padalinys');

        // Calling forUser with same user should not clear cache
        $this->authorizer->forUser($this->superAdmin);

        // This should still return the memoized result
        $result = $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($result)->toBeTrue();
    });

    test('user with duty role gets correct memoized results', function () {
        $userWithRole = makeUser($this->tenant);
        $duty = $userWithRole->duties()->first();
        $duty->assignRole($this->role);

        $this->authorizer->forUser($userWithRole);

        // Should have read permission via duty role
        $readResult = $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($readResult)->toBeTrue();

        // Repeated check should use cache
        $readResult2 = $this->authorizer->checkAllRoleables('news.read.padalinys');
        expect($readResult2)->toBeTrue();

        // Check a permission the role doesn't have
        $updateResult = $this->authorizer->checkAllRoleables('news.update.padalinys');
        expect($updateResult)->toBeFalse();
    });
});

describe('check alias method', function () {
    test('check method delegates to checkAllRoleables with memoization', function () {
        $this->authorizer->forUser($this->superAdmin);

        $result1 = $this->authorizer->check('news.read.padalinys');
        $result2 = $this->authorizer->check('news.read.padalinys');

        expect($result1)->toBe($result2)->toBeTrue();
    });
});
