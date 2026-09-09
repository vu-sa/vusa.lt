<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\QuickLink;
use App\Models\Relationship;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\RelationshipService;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create related test data
    $this->page = Page::factory()->for($this->tenant)->create();
    $this->news = News::factory()->for($this->tenant)->create();
    $this->quickLink = QuickLink::factory()->for($this->tenant)->create();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});

describe('atstovavimas dashboard', function (): void {
    test('admin can access atstovavimas dashboard', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('userInstitutions')
                ->has('availableTenants')
                ->missing('tenantInstitutions')
                ->missing('representativeActivity')
            );
    });

    test('regular user can access atstovavimas dashboard', function (): void {
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('userInstitutions')
                ->has('availableTenants')
            );
    });

    test('atstovavimas filters PKP tenants', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', fn ($tenants) => collect($tenants)->every(fn ($tenant) => $tenant['type'] !== 'pkp'))
            );
    });

    test('atstovavimas provides accessible institutions and available tenants', function (): void {
        // Give the admin the institutions.read.padalinys permission so they can see tenant data
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->admin->current_duties->first();
        if ($duty) {
            $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $duty->assignRole($role);
        }

        $response = asUser($this->admin)->get(route('dashboard.atstovavimas'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->has('availableTenants')
                ->where('availableTenants', function ($tenants) {
                    // Convert to collection if it's an array, or keep as collection
                    $collection = collect($tenants);

                    // Should have at least one tenant and not include PKP type
                    return $collection->count() > 0 &&
                           $collection->every(fn ($tenant) => $tenant['type'] !== 'pkp');
                })
            );
    });
});

describe('atstovavimas dashboard authorization', function (): void {
    test('regular user only sees their assigned institutions', function (): void {
        // Regular user without coordinator role should only see their own institution
        $userInstitutionId = $this->user->current_duties->first()->institution_id;

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->where('userInstitutions', function ($institutions) use ($userInstitutionId) {
                    $collection = collect($institutions);
                    $institution = $collection->firstWhere('id', $userInstitutionId);

                    // Should only contain the user's assigned institution
                    return $collection->count() >= 1 &&
                           $institution !== null &&
                           isset($institution['activity_status']['status']) &&
                           array_key_exists('effective_days_since_activity', $institution['activity_status']);
                })
            );
    });

    test('regular user has no available tenants for tenant tab', function (): void {
        // Regular user without coordinator role should not see the tenant tab
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // Regular users should have empty availableTenants
                    return $collection->isEmpty();
                })
            );
    });

    test('user with global read permission sees all tenants', function (): void {
        $mainTenant = Tenant::factory()->create(['type' => 'pagrindinis']);
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);

        // Create a user with institutions.read.* permission (global access)
        $globalPermission = Permission::firstOrCreate(['name' => 'institutions.read.*', 'guard_name' => 'web']);
        $globalRole = Role::firstOrCreate([
            'name' => 'Global Institution Reader',
            'guard_name' => 'web',
        ]);
        $globalRole->givePermissionTo($globalPermission);

        $globalUser = makeTenantUserWithRole($globalRole->name, $mainTenant);

        asUser($globalUser)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', function ($tenants) use ($mainTenant, $otherTenant) {
                    $collection = collect($tenants);

                    return $collection->contains(fn ($tenant) => $tenant['id'] == $mainTenant->id)
                        && $collection->contains(fn ($tenant) => $tenant['id'] == $otherTenant->id);
                })
            );
    });

    test('user with padalinys read permission sees their tenants', function (): void {
        // Give the admin the institutions.read.padalinys permission
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->admin->current_duties->first();
        if ($duty) {
            $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
            $duty->assignRole($role);
        }

        // The admin should have available tenants for the tenant tab
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->where('availableTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // User with permission should have available tenants for the tenant tab
                    return $collection->isNotEmpty();
                })
            );
    });

    test('super admin sees all institutions across tenants via tenant tab', function (): void {
        $superAdmin = makeAdminUser($this->tenant);

        // Create an institution in a different tenant
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        // Verify super admin has access to all tenants via availableTenants
        asUser($superAdmin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->where('availableTenants', function ($tenants) use ($otherTenant) {
                    $collection = collect($tenants);

                    // Super admin should see all non-PKP tenants including the other tenant
                    return $collection->isNotEmpty() &&
                           $collection->contains(fn ($t) => $t['id'] == $otherTenant->id);
                })
            );
    });
});

describe('atstovavimas dashboard periodicity', function (): void {
    test('user institutions include meeting_periodicity_days', function (): void {
        // Create a non-PKP tenant to ensure institution is not filtered out
        $nonPkpTenant = Tenant::factory()->create(['type' => 'padalinys']);

        // Create a user with an assigned institution that has custom periodicity
        $institution = Institution::factory()->for($nonPkpTenant)->create([
            'meeting_periodicity_days' => 21,
            'alias' => 'periodicity-test-'.uniqid(),
        ]);

        // Create a duty and assign it to the user
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        // Create a fresh user for this test to avoid interference
        $testUser = User::factory()->create();
        $testUser->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        asUser($testUser)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->where('userInstitutions', function ($institutions) use ($institution) {
                    $collection = collect($institutions);

                    // If no institutions found, the closure returns false which fails the test
                    if ($collection->isEmpty()) {
                        return false;
                    }

                    $testInstitution = $collection->firstWhere('id', $institution->id);

                    // Institution should have meeting_periodicity_days appended
                    return $testInstitution !== null &&
                           isset($testInstitution['meeting_periodicity_days']) &&
                           $testInstitution['meeting_periodicity_days'] === 21;
                })
            );
    });

    test('user institutions use type periodicity when no override', function (): void {
        // Create a type with custom periodicity
        $institutionType = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'extra_attributes' => ['meeting_periodicity_days' => 14],
        ]);

        // Create an institution with no override
        $institution = Institution::factory()->for($this->tenant)->create([
            'meeting_periodicity_days' => null,
            'alias' => 'periodicity-type-test-'.uniqid(),
        ]);
        $institution->types()->attach($institutionType);

        // Create a duty and assign it to the user
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        // Create a fresh user for this test to avoid interference
        $testUser = User::factory()->create();
        $testUser->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        asUser($testUser)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('userInstitutions', function ($institutions) use ($institution) {
                    $collection = collect($institutions);
                    $testInstitution = $collection->firstWhere('id', $institution->id);

                    // Institution should inherit type's periodicity
                    return $testInstitution !== null &&
                           isset($testInstitution['meeting_periodicity_days']) &&
                           $testInstitution['meeting_periodicity_days'] === 14;
                })
            );
    });
});

describe('atstovavimas tenant isolation', function (): void {
    beforeEach(function (): void {
        // Give Communication Coordinators the institutions.read.padalinys permission
        // This replaces the old role-based visibility settings
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $coordinatorRole = Role::where('name', 'Communication Coordinator')->first();
        if ($coordinatorRole && ! $coordinatorRole->hasPermissionTo($permission)) {
            $coordinatorRole->givePermissionTo($permission);
        }
    });

    test('user sees institutions and tenants based on their permissions', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('userInstitutions')
                ->has('availableTenants')
                ->where('availableTenants',
                    // User should see tenants they have permissions for
                    fn ($tenants) => collect($tenants)->count() > 0)
            );
    });
});

describe('atstovavimas related institutions', function (): void {
    beforeEach(function (): void {
        // Create a relationship type
        $this->relationship = new Relationship([
            'name' => 'Test Relationship',
            'slug' => 'test-relationship-'.uniqid(),
            'description' => 'Test relationship for dashboard',
        ]);
        $this->relationship->save();

        // Get user's institution
        $this->userInstitution = $this->user->current_duties->first()->institution;

        // Create a related institution in the same tenant
        $this->relatedInstitution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Susijusi institucija', 'en' => 'Related Institution'],
        ]);
    });

    test('atstovavimas returns mayHaveRelatedInstitutions flag', function (): void {
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('mayHaveRelatedInstitutions')
            );
    });

    test('relatedInstitutions lazy load returns institutions with outgoing relationship', function (): void {
        // Create outgoing relationship (user's institution -> related)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->userInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->userInstitution->id);

        // Use reloadOnly to test the lazy-loaded relatedInstitutions prop
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->missing('relatedInstitutions') // Lazy props are not in initial response
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions')
                    ->where('relatedInstitutions', function ($institutions) {
                        $collection = collect($institutions);
                        if ($collection->isEmpty()) {
                            return false;
                        }

                        // Should have the related institution
                        $found = $collection->firstWhere('id', $this->relatedInstitution->id);
                        if (! $found) {
                            return false;
                        }

                        // Should be marked as related with correct metadata
                        return $found['is_related'] === true &&
                               $found['authorized'] === true &&
                               $found['relationship_direction'] === 'outgoing';
                    })
                )
            );
    });

    test('relatedInstitutions returns incoming relationships with authorized = false when not bidirectional', function (): void {
        // Create incoming relationship (related -> user's institution, NOT bidirectional)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->userInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->userInstitution->id);

        // Use reloadOnly to test the lazy-loaded relatedInstitutions prop
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->missing('relatedInstitutions')
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions')
                    ->where('relatedInstitutions', function ($institutions) {
                        $collection = collect($institutions);
                        if ($collection->isEmpty()) {
                            return false;
                        }

                        // Should have the related institution
                        $found = $collection->firstWhere('id', $this->relatedInstitution->id);
                        if (! $found) {
                            return false;
                        }

                        // Should be marked as incoming with authorized = false
                        return $found['is_related'] === true &&
                               $found['authorized'] === false &&
                               $found['relationship_direction'] === 'incoming';
                    })
                )
            );
    });

    test('relatedInstitutions returns incoming relationships with authorized = true when bidirectional', function (): void {
        // Create incoming relationship (related -> user's institution, IS bidirectional)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->userInstitution->id,
            'bidirectional' => true,
        ]);
        $relationshipable->save();

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->userInstitution->id);

        // Use reloadOnly to test the lazy-loaded relatedInstitutions prop
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->missing('relatedInstitutions')
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions')
                    ->where('relatedInstitutions', function ($institutions) {
                        $collection = collect($institutions);
                        if ($collection->isEmpty()) {
                            return false;
                        }

                        // Should have the related institution
                        $found = $collection->firstWhere('id', $this->relatedInstitution->id);
                        if (! $found) {
                            return false;
                        }

                        // Should be marked as incoming with authorized = true (bidirectional!)
                        return $found['is_related'] === true &&
                               $found['authorized'] === true &&
                               $found['relationship_direction'] === 'incoming';
                    })
                )
            );
    });

    test('authorized related institutions include meetings with agenda items', function (): void {
        // Create outgoing relationship (authorized)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->userInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        // Create meeting with agenda item
        $meeting = Meeting::factory()->create(['start_time' => now()]);
        $meeting->institutions()->attach($this->relatedInstitution->id);
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
        ]);

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->userInstitution->id);

        // Use reloadOnly to test the lazy-loaded relatedInstitutions prop
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->missing('relatedInstitutions')
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions')
                    ->where('relatedInstitutions', function ($institutions) {
                        $collection = collect($institutions);
                        $found = $collection->firstWhere('id', $this->relatedInstitution->id);
                        if (! $found) {
                            return false;
                        }

                        // Authorized institution should have meetings with agenda items
                        $meetings = collect($found['meetings'] ?? []);
                        if ($meetings->isEmpty()) {
                            return false;
                        }

                        $firstMeeting = $meetings->first();
                        $agendaItems = collect($firstMeeting['agenda_items'] ?? []);

                        return $agendaItems->isNotEmpty();
                    })
                )
            );
    });

    test('unauthorized related institutions include meetings but no agenda items', function (): void {
        // Create incoming relationship (NOT authorized because NOT bidirectional)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->userInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        // Create meeting and attach to related institution
        $meeting = Meeting::factory()->create(['start_time' => now()]);
        $meeting->institutions()->attach($this->relatedInstitution->id);

        // Create agenda item for this meeting (should NOT be loaded for unauthorized)
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
        ]);

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->userInstitution->id);

        // Use reloadOnly to test the lazy-loaded relatedInstitutions prop
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->missing('relatedInstitutions')
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions')
                    ->where('relatedInstitutions', function ($institutions) {
                        $collection = collect($institutions);
                        $found = $collection->firstWhere('id', $this->relatedInstitution->id);

                        if (! $found) {
                            return false;
                        }

                        // Institution should be unauthorized
                        if ($found['authorized'] !== false) {
                            return false;
                        }

                        // Should have meetings
                        $meetings = collect($found['meetings'] ?? []);
                        if ($meetings->isEmpty()) {
                            return false;
                        }

                        $firstMeeting = $meetings->first();

                        // For unauthorized institutions, agenda_items should NOT be loaded
                        // Since Laravel doesn't eager load them, the key should be missing or empty
                        $hasAgendaItems = isset($firstMeeting['agenda_items']) && ! empty($firstMeeting['agenda_items']);

                        return ! $hasAgendaItems;
                    })
                )
            );
    });
});
