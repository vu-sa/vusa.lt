<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    // Clear any cached settings
    app()->forgetInstance(AtstovavimasSettings::class);
});

afterEach(function () {
    // Clean up settings after each test
    $settings = app(AtstovavimasSettings::class);
    $settings->coordinator_role_ids = [];
    $settings->save();
    app()->forgetInstance(AtstovavimasSettings::class);
});

describe('atstovavimas settings page access', function () {
    test('super admin can access atstovavimas settings page', function () {
        asUser($this->admin)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/EditAtstovavimasSettings')
                ->has('roles')
                ->has('coordinator_role_ids')
            );
    });

    test('regular user cannot access atstovavimas settings page', function () {
        asUser($this->user)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(403);
    });
});

describe('atstovavimas settings update', function () {
    test('super admin can update coordinator roles', function () {
        $role = Role::first();

        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'coordinator_role_ids' => [$role->id],
            ])
            ->assertRedirect();

        // Verify settings were updated
        app()->forgetInstance(AtstovavimasSettings::class);
        $settings = app(AtstovavimasSettings::class);

        expect($settings->getCoordinatorRoleIds()->toArray())->toBe([$role->id]);
    });

    test('regular user cannot update coordinator roles', function () {
        $role = Role::first();

        asUser($this->user)
            ->post(route('settings.atstovavimas.update'), [
                'coordinator_role_ids' => [$role->id],
            ])
            ->assertStatus(403);
    });

    test('coordinator roles can be cleared', function () {
        // First set some roles
        $role = Role::first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$role->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Then clear them
        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'coordinator_role_ids' => [],
            ])
            ->assertRedirect();

        app()->forgetInstance(AtstovavimasSettings::class);
        $settings = app(AtstovavimasSettings::class);

        expect($settings->getCoordinatorRoleIds()->toArray())->toBe([]);
    });

    test('invalid role ids are rejected', function () {
        asUser($this->admin)
            ->post(route('settings.atstovavimas.update'), [
                'coordinator_role_ids' => ['invalid-ulid', 'another-invalid'],
            ])
            ->assertSessionHasErrors('coordinator_role_ids.0');
    });
});

describe('coordinator tenant visibility', function () {
    test('user with coordinator role has access to tenant tab', function () {
        // Create a coordinator role and assign it to user's duty
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        expect($coordinatorRole)->not->toBeNull();

        // Create additional institution in the same tenant
        $extraInstitution = Institution::factory()->for($this->tenant)->create();

        // Configure the coordinator role in settings
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Create user with coordinator role
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        // Clear any cached coordinator tenant IDs
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        asUser($coordinator)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                // userInstitutions only contains directly assigned institutions
                ->has('userInstitutions')
                ->where('availableTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // Coordinator should have available tenants (access to tenant tab)
                    return $collection->isNotEmpty() &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });

    test('coordinator can lazy load tenant institutions', function () {
        // Create a coordinator role and assign it to user's duty
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        expect($coordinatorRole)->not->toBeNull();

        // Create additional institution in the same tenant
        $extraInstitution = Institution::factory()->for($this->tenant)->create();

        // Configure the coordinator role in settings
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Create user with coordinator role
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        // Verify the coordinator has access to available tenants
        asUser($coordinator)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // Coordinator should have available tenants
                    return $collection->isNotEmpty() &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });

    test('user without coordinator role only sees assigned institutions', function () {
        // Configure a coordinator role but don't give it to the user
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

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

                    // Regular user should NOT see the extra institution
                    return $collection->doesntContain(fn ($inst) => $inst['id'] == $extraInstitution->id) &&
                           $collection->contains(fn ($inst) => $inst['id'] == $userInstitutionId);
                })
                ->where('availableTenants', function ($tenants) {
                    // Regular user should have no available tenants
                    return collect($tenants)->isEmpty();
                })
            );
    });

    test('coordinator in one tenant only sees that tenant institutions', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        // Create a second tenant with an institution
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        // Create user with coordinator role in first tenant only
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        asUser($coordinator)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('userInstitutions', function ($institutions) use ($otherInstitution) {
                    $collection = collect($institutions);

                    // Coordinator should NOT see institutions from other tenant
                    return $collection->doesntContain(fn ($inst) => $inst['id'] == $otherInstitution->id);
                })
                ->where('availableTenants', function ($tenants) use ($otherTenant) {
                    $collection = collect($tenants);

                    // Should not include the other tenant
                    return $collection->doesntContain(fn ($t) => $t['id'] == $otherTenant->id);
                })
            );
    });
});

describe('coordinator cache management', function () {
    test('coordinator tenant ids are cached', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        // Clear cache first
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        // Get coordinator tenant IDs (this should cache them)
        $settings = app(AtstovavimasSettings::class);
        $tenantIds1 = $settings->getCoordinatorTenantIds($coordinator);

        // Verify cache key exists
        $cacheKey = AtstovavimasSettings::getCoordinatorCacheKey($coordinator->id);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Get again (should use cache)
        $tenantIds2 = $settings->getCoordinatorTenantIds($coordinator);

        expect($tenantIds1->toArray())->toBe($tenantIds2->toArray());
    });

    test('cache is cleared when duty role changes', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        // Get coordinator tenant IDs to cache them
        $settings = app(AtstovavimasSettings::class);
        $settings->getCoordinatorTenantIds($coordinator);

        $cacheKey = AtstovavimasSettings::getCoordinatorCacheKey($coordinator->id);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear coordinator cache manually (simulating what observer does)
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('empty coordinator role ids returns empty collection without caching', function () {
        // Ensure no coordinator roles are configured
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        $tenantIds = $settings->getCoordinatorTenantIds($this->user);

        expect($tenantIds->isEmpty())->toBeTrue();

        // Should not have created a cache entry since we returned early
        $cacheKey = AtstovavimasSettings::getCoordinatorCacheKey($this->user->id);
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('settings helper methods', function () {
    test('userHasCoordinatorRole returns true for coordinators', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
        AtstovavimasSettings::clearCoordinatorCache($coordinator->id);

        $settings = app(AtstovavimasSettings::class);
        expect($settings->userHasCoordinatorRole($coordinator))->toBeTrue();
    });

    test('userHasCoordinatorRole returns false for non-coordinators', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        expect($settings->userHasCoordinatorRole($this->user))->toBeFalse();
    });

    test('getCoordinatorRoleNames returns role names', function () {
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        $settings = app(AtstovavimasSettings::class);
        $settings->coordinator_role_ids = [$coordinatorRole->id];
        $settings->save();
        app()->forgetInstance(AtstovavimasSettings::class);

        $settings = app(AtstovavimasSettings::class);
        $names = $settings->getCoordinatorRoleNames();

        expect($names->toArray())->toContain('Communication Coordinator');
    });
});
