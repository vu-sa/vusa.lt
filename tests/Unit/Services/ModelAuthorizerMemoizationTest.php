<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

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
        if (! Permission::where('name', $permission)->exists()) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    $this->role = Role::firstOrCreate(['name' => 'Test Memoization Role', 'guard_name' => 'web']);
    $this->role->givePermissionTo(['news.read.padalinys', 'news.create.padalinys']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(config('permission.super_admin_role_name'));

    $this->normalUser = makeUser($this->tenant);

    $this->authorizer = new ModelAuthorizer;
});

describe('request-level memoization', function (): void {
    test('repeated permission check returns cached result', function (): void {
        $result1 = $this->authorizer->allows($this->superAdmin, 'news.read.padalinys');
        $result2 = $this->authorizer->allows($this->superAdmin, 'news.read.padalinys');

        expect($result1)->toBe($result2)
            ->toBeTrue();
    });

    test('different permissions are cached independently', function (): void {
        $readResult = $this->authorizer->allows($this->normalUser, 'news.read.padalinys');
        $updateResult = $this->authorizer->allows($this->normalUser, 'news.update.padalinys');

        // Both should be false for normal user without role
        expect($readResult)->toBeFalse();
        expect($updateResult)->toBeFalse();
    });

    test('super admin returns true for all permission checks', function (): void {
        expect($this->authorizer->allows($this->superAdmin, 'news.read.padalinys'))->toBeTrue()
            ->and($this->authorizer->allows($this->superAdmin, 'news.create.padalinys'))->toBeTrue()
            ->and($this->authorizer->allows($this->superAdmin, 'news.update.padalinys'))->toBeTrue()
            ->and($this->authorizer->allows($this->superAdmin, 'nonexistent.permission.scope'))->toBeTrue();
    });

    test('one users result is never reused for another', function (): void {
        // Super admin has all permissions
        expect($this->authorizer->allows($this->superAdmin, 'news.read.padalinys'))->toBeTrue();

        // The normal user must not inherit that cached result
        expect($this->authorizer->allows($this->normalUser, 'news.read.padalinys'))->toBeFalse();

        // ...and resolving the normal user must not have evicted the super admin's
        expect($this->authorizer->allows($this->superAdmin, 'news.read.padalinys'))->toBeTrue();
    });

    test('user with duty role gets correct memoized results', function (): void {
        $userWithRole = makeUser($this->tenant);
        $duty = $userWithRole->duties()->first();
        $duty->assignRole($this->role);

        // Should have read permission via duty role
        $readResult = $this->authorizer->allows($userWithRole, 'news.read.padalinys');
        expect($readResult)->toBeTrue();

        // Repeated check should use cache
        $readResult2 = $this->authorizer->allows($userWithRole, 'news.read.padalinys');
        expect($readResult2)->toBeTrue();

        // Check a permission the role doesn't have
        $updateResult = $this->authorizer->allows($userWithRole, 'news.update.padalinys');
        expect($updateResult)->toBeFalse();
    });
});

describe('derived accessors', function (): void {
    test('allows, tenants and duties all read from the same memoized resolution', function (): void {
        $userWithRole = makeUser($this->tenant);
        $userWithRole->duties()->first()->assignRole($this->role);

        $scope = $this->authorizer->scope($userWithRole, 'news.read.padalinys');

        expect($this->authorizer->allows($userWithRole, 'news.read.padalinys'))->toBe($scope->granted)
            ->and($this->authorizer->tenants($userWithRole, 'news.read.padalinys'))->toBe($scope->tenants)
            ->and($this->authorizer->duties($userWithRole, 'news.read.padalinys'))->toBe($scope->duties);
    });
});
