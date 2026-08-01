<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    // Clear any cached settings
    app()->forgetInstance(AtstovavimasSettings::class);
});

afterEach(function (): void {
    // Clean up settings after each test
    $settings = app(AtstovavimasSettings::class);
    $settings->institution_manager_role_id = null;
    $settings->save();
    app()->forgetInstance(AtstovavimasSettings::class);
});

describe('atstovavimas settings page access', function (): void {
    test('super admin can access atstovavimas settings page', function (): void {
        asUser($this->admin)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/EditAtstovavimasSettings')
                ->has('roles')
                ->has('institution_manager_role_id')
            );
    });

    test('regular user cannot access atstovavimas settings page', function (): void {
        asUser($this->user)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(403);
    });
});

describe('atstovavimas settings update', function (): void {
    test('super admin can update institution manager role', function (): void {
        $role = Role::first();

        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'institution_manager_role_id' => $role->id,
            ])
            ->assertRedirect();

        // Verify settings were updated
        app()->forgetInstance(AtstovavimasSettings::class);
        $settings = app(AtstovavimasSettings::class);

        expect($settings->getInstitutionManagerRoleId())->toBe($role->id);
    });

    test('regular user cannot update institution manager role', function (): void {
        $role = Role::first();

        asUser($this->user)
            ->post(route('settings.atstovavimas.update'), [
                'institution_manager_role_id' => $role->id,
            ])
            ->assertStatus(403);
    });

    test('institution manager role can be cleared', function (): void {
        // First set a role
        $role = Role::first();
        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $role->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Then clear it
        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'institution_manager_role_id' => null,
            ])
            ->assertRedirect();

        app()->forgetInstance(AtstovavimasSettings::class);
        $settings = app(AtstovavimasSettings::class);

        expect($settings->getInstitutionManagerRoleId())->toBeNull();
    });

    test('invalid role id is rejected', function (): void {
        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'institution_manager_role_id' => 'invalid-ulid',
            ])
            ->assertSessionHasErrors('institution_manager_role_id');
    });
});

describe('permission-based tenant visibility', function (): void {
    test('user with institutions.read.padalinys permission sees tenant tab', function (): void {
        // Give user the permission via their duty's role
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->user->current_duties->first();
        $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $duty->assignRole($role);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->where('availableTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // User with permission should have available tenants
                    return $collection->isNotEmpty() &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });

    test('user without read permission only sees assigned institutions', function (): void {
        // Create additional institution in the same tenant
        $extraInstitution = Institution::factory()->for($this->tenant)->create();

        // Regular user's assigned institution
        $userInstitutionId = $this->user->current_duties->first()->institution_id;

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('userInstitutions', function ($institutions) use ($userInstitutionId, $extraInstitution) {
                    $collection = collect($institutions);

                    // Regular user should NOT see the extra institution (not assigned via duty)
                    return $collection->doesntContain(fn ($inst) => $inst['id'] == $extraInstitution->id) &&
                           $collection->contains(fn ($inst) => $inst['id'] == $userInstitutionId);
                })
                ->where('availableTenants',
                    // Regular user should have no available tenants
                    fn ($tenants) => collect($tenants)->isEmpty())
            );
    });

    test('user with permission in one tenant only sees that tenant', function (): void {
        // Give user the permission via their duty's role
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->user->current_duties->first();
        $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $duty->assignRole($role);

        // Create a second tenant with an institution
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', function ($tenants) use ($otherTenant) {
                    $collection = collect($tenants);

                    // Should not include the other tenant
                    return $collection->doesntContain(fn ($t) => $t['id'] == $otherTenant->id) &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });
});

describe('institution manager role', function (): void {
    test('userIsInstitutionManager returns true for users with manager role', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Assign the role to user's duty
        $duty = $this->user->current_duties->first();
        $duty->assignRole($managerRole);

        AtstovavimasSettings::clearManagerCache($this->user->id);

        $settings = app(AtstovavimasSettings::class);
        expect($settings->userIsInstitutionManager($this->user))->toBeTrue();
    });

    test('userIsInstitutionManager returns false for non-managers', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Don't assign the role to user
        $settings = app(AtstovavimasSettings::class);
        expect($settings->userIsInstitutionManager($this->user))->toBeFalse();
    });

    test('getManagerTenantIds returns tenants where user has manager role', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Assign the role to user's duty
        $duty = $this->user->current_duties->first();
        $duty->assignRole($managerRole);

        AtstovavimasSettings::clearManagerCache($this->user->id);

        $settings = app(AtstovavimasSettings::class);
        $tenantIds = $settings->getManagerTenantIds($this->user);

        expect($tenantIds->toArray())->toContain($this->tenant->id);
    });

    test('getInstitutionManagerRoleName returns role name', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        expect($settings->getInstitutionManagerRoleName())->toBe('Student Rep Coordinator Test');
    });

    test('getInstitutionManagerRoleName returns null when no role configured', function (): void {
        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = null;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        expect($settings->getInstitutionManagerRoleName())->toBeNull();
    });
});

describe('manager cache management', function (): void {
    test('manager tenant ids are cached', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Assign the role to user's duty
        $duty = $this->user->current_duties->first();
        $duty->assignRole($managerRole);

        // Clear cache first
        AtstovavimasSettings::clearManagerCache($this->user->id);

        // Get manager tenant IDs (this should cache them)
        $settings = app(AtstovavimasSettings::class);
        $tenantIds1 = $settings->getManagerTenantIds($this->user);

        // Verify cache key exists
        $cacheKey = AtstovavimasSettings::getManagerTenantsCacheKey($this->user->id);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Get again (should use cache)
        $tenantIds2 = $settings->getManagerTenantIds($this->user);

        expect($tenantIds1->toArray())->toBe($tenantIds2->toArray());
    });

    test('cache is cleared when clearManagerCache is called', function (): void {
        $managerRole = Role::firstOrCreate(['name' => 'Student Rep Coordinator Test', 'guard_name' => 'web']);

        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $managerRole->id;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Assign the role to user's duty
        $duty = $this->user->current_duties->first();
        $duty->assignRole($managerRole);

        // Get manager tenant IDs to cache them
        $settings = app(AtstovavimasSettings::class);
        $settings->getManagerTenantIds($this->user);

        $cacheKey = AtstovavimasSettings::getManagerTenantsCacheKey($this->user->id);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear cache
        AtstovavimasSettings::clearManagerCache($this->user->id);

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('empty manager role id returns empty collection without caching', function (): void {
        // Ensure no manager role is configured
        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = null;
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        $tenantIds = $settings->getManagerTenantIds($this->user);

        expect($tenantIds->isEmpty())->toBeTrue();

        // Should not have created a cache entry since we returned early
        $cacheKey = AtstovavimasSettings::getManagerTenantsCacheKey($this->user->id);
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});
