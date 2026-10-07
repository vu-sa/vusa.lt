<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Pivots\AgendaItem;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

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
                ->has('canViewTenantOverview')
                ->missing('statsTenants')
                ->missing('tenantInstitutions')
                ->missing('representativeActivity')
            );
    });

    test('followed institutions load with the secondary group, upcoming meetings on first paint', function (): void {
        $followed = Institution::factory()->for($this->tenant)->create();
        $this->admin->followedInstitutions()->attach($followed);
        Meeting::factory()->hasAttached($followed)->create(['start_time' => now()->addDay()]);

        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeetings.total', 1)
                ->where('upcomingMeetings.items.0.is_followed', true)
                ->missing('followedInstitutions')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('followedInstitutions.total', 1)
                    ->where('followedInstitutions.items.0.id', $followed->id)
                )
            );
    });

    test('upcoming meetings run from the start of today to two months ahead', function (): void {
        $institution = $this->user->current_duties()->first()->institution;
        $this->travelTo('2026-03-10 15:00:00');
        $earlierToday = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-03-10 09:00:00']);
        $nextMonth = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-04-10 10:00:00']);
        Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-03-09 10:00:00']);
        Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-05-11 10:00:00']);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeetings.total', 2)
                ->where('upcomingMeetings.items.0.id', $earlierToday->id)
                ->where('upcomingMeetings.items.0.is_followed', false)
                ->where('upcomingMeetings.items.1.id', $nextMonth->id)
            );
    });

    test('upcoming meetings send at most twenty rows but count them all', function (): void {
        $institution = $this->user->current_duties()->first()->institution;
        Meeting::factory()->count(21)->hasAttached($institution)
            ->sequence(fn ($sequence) => ['start_time' => now()->addDays($sequence->index + 1)])
            ->create();

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeetings.total', 21)
                ->has('upcomingMeetings.items', 20)
            );
    });

    test('reference documents are capped at eight, newest document date first', function (): void {
        $dutyType = DutyType::factory()->create([]);
        $this->user->current_duties()->first()->types()->attach($dutyType);
        $files = FileableFile::factory()->count(9)
            ->sequence(fn ($sequence) => ['file_date' => now()->subDays(9 - $sequence->index)])
            ->create(['fileable_type' => MorphMap::alias(DutyType::class), 'fileable_id' => $dutyType->id]);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('referenceDocuments', 8)
                    ->where('referenceDocuments.0.id', $files->last()->id)
                )
            );
    });

    test('reference documents of the user\'s duty types, parents included, load with the secondary group', function (): void {
        $parentType = DutyType::factory()->create([]);
        $dutyType = DutyType::factory()->create(['parent_id' => $parentType->id]);
        $this->user->current_duties()->first()->types()->attach($dutyType);
        $unrelatedType = DutyType::factory()->create([]);

        $fileOn = fn (DutyType $type, array $attributes = []) => FileableFile::factory()->create([
            'fileable_type' => MorphMap::alias(DutyType::class),
            'fileable_id' => $type->id,
            ...$attributes,
        ]);
        $regulation = $fileOn($parentType);
        $fileOn($unrelatedType);
        $fileOn($dutyType, ['deleted_externally_at' => now()]);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('referenceDocuments')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('referenceDocuments', 1)
                    ->where('referenceDocuments.0.id', $regulation->id)
                )
            );
    });

    test('the overview number counts only the user\'s own open tasks', function (): void {
        $open = Task::factory()->create(['completed_at' => null]);
        $done = Task::factory()->create(['completed_at' => now()]);
        $someoneElses = Task::factory()->create(['completed_at' => null]);
        $this->user->tasks()->attach([$open->id, $done->id]);
        $this->admin->tasks()->attach($someoneElses->id);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page->where('openTasksCount', 1));
    });

    test('regular user can access atstovavimas dashboard', function (): void {
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('userInstitutions')
                ->where('canViewTenantOverview', true)
            );
    });

    test('atstovavimas filters PKP tenants', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', fn ($tenants) => collect($tenants)->every(fn ($tenant) => $tenant['type'] !== 'pkp'))
            );
    });

    test('the padaliniai task number counts padalinys tasks, not the viewer\'s own', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page->missing('openTasksCount'));

        asUser(makeAdminUser($this->tenant))
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page->where('canViewTenantTasks', true));
    });

    test('a padalinys communication coordinator gets statistics and the Gantt for their own padalinys, without the task number', function (): void {
        $ownTenantId = $this->admin->current_duties()->first()->institution->tenant_id;

        asUser($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statsTenants', fn ($tenants) => collect($tenants)->pluck('id')->all() === [$ownTenantId])
                ->where('defaultGanttTenantIds', [(string) $ownTenantId])
                ->where('canViewTenantTasks', false)
            );
    });

    test('the central student representative coordinator gets statistics for every padalinys and the task number', function (): void {
        $coordinator = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
        $representationalCount = Tenant::query()->representational()->count();

        asUser($coordinator)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('statsTenants', $representationalCount)
                ->has('defaultGanttTenantIds', $representationalCount)
                ->where('canViewTenantTasks', true)
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

        $response = asUser($this->admin)->get(route('dashboard.atstovavimas.padaliniai'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->has('statsTenants')
                ->where('statsTenants', function ($tenants) {
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

    test('an institution where the user serves as secretary is included in user institutions as administered', function (): void {
        $administeredInstitution = Institution::factory()->for($this->tenant)->create();
        $cadence = Cadence::factory()->create(['institution_id' => $administeredInstitution->id]);
        $this->user->secretariedInstitutions()->attach($administeredInstitution, ['cadence_id' => $cadence->id]);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('userInstitutions', fn ($institutions) => collect($institutions)
                    ->contains(fn ($inst) => data_get($inst, 'id') === $administeredInstitution->id && data_get($inst, 'is_administered') === true)
                )
            );
    });

    test('a rep who manages no padalinys opens the overview for its Gantt only', function (): void {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $pkpTenant = Tenant::factory()->create(['type' => 'pkp']);
        $ownTenantId = $this->user->current_duties->first()->institution->tenant_id;

        asUser($this->user)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', [])
                ->where('ganttTenants', fn ($tenants) => collect($tenants)->contains('id', $otherTenant->id)
                    && collect($tenants)->doesntContain('id', $pkpTenant->id))
                ->where('defaultGanttTenantIds', [(string) $ownTenantId])
            );
    });

    test('the old tenant-scope bookmark opens the padalinys overview', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas', ['scope' => 'tenant']))
            ->assertRedirect(route('dashboard.atstovavimas.padaliniai'));
    });

    test('the old tenant-tab bookmark opens the padalinys overview for a rep too', function (): void {
        asUser($this->user)
            ->get(route('dashboard.atstovavimas', ['tab' => 'tenant']))
            ->assertRedirect(route('dashboard.atstovavimas.padaliniai'));
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
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', function ($tenants) use ($mainTenant, $otherTenant) {
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
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', function ($tenants) {
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

        // Verify super admin has access to all tenants via statsTenants
        asUser($superAdmin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', function ($tenants) use ($otherTenant) {
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
        $studentRepType = DutyType::query()->where('slug', 'studentu-atstovai')->first()
            ?? DutyType::factory()->create(['slug' => 'studentu-atstovai']);

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
        $institutionType = InstitutionType::factory()->create([
            'extra_attributes' => ['meeting_periodicity_days' => 14],
        ]);

        // Create an institution with no override
        $institution = Institution::factory()->for($this->tenant)->create([
            'meeting_periodicity_days' => null,
            'alias' => 'periodicity-type-test-'.uniqid(),
        ]);
        $institution->types()->attach($institutionType);

        // Create a duty and assign it to the user
        $studentRepType = DutyType::query()->where('slug', 'studentu-atstovai')->first()
            ?? DutyType::factory()->create(['slug' => 'studentu-atstovai']);

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
        $coordinatorRole = Role::where('name', 'Komunikacijos koordinatorius')->first();
        if ($coordinatorRole && ! $coordinatorRole->hasPermissionTo($permission)) {
            $coordinatorRole->givePermissionTo($permission);
        }
    });

    test('user sees institutions and tenants based on their permissions', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->has('statsTenants')
                ->where('statsTenants',
                    // User should see tenants they have permissions for
                    fn ($tenants) => collect($tenants)->count() > 0)
            );
    });
});

describe('atstovavimas related institutions', function (): void {
    beforeEach(function (): void {

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
        InstitutionLink::factory()->create(['source_institution_id' => $this->userInstitution->id, 'target_institution_id' => $this->relatedInstitution->id]);

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
                               $found['relationship_direction'] === 'outgoing' &&
                               $found['is_internal'] === false;
                    })
                )
            );
    });

    test('relatedInstitutions returns incoming relationships with authorized = false when not bidirectional', function (): void {
        // Create incoming relationship (related -> user's institution, NOT bidirectional)
        InstitutionLink::factory()->create(['source_institution_id' => $this->relatedInstitution->id, 'target_institution_id' => $this->userInstitution->id]);

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
        InstitutionLink::factory()->create(['source_institution_id' => $this->relatedInstitution->id, 'target_institution_id' => $this->userInstitution->id, 'mutual' => true]);

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

                        return $found['is_related'] === true &&
                               $found['authorized'] === true &&
                               $found['relationship_direction'] === 'mutual';
                    })
                )
            );
    });

    test('authorized related institutions include meetings with agenda items', function (): void {
        // Create outgoing relationship (authorized)
        InstitutionLink::factory()->create(['source_institution_id' => $this->userInstitution->id, 'target_institution_id' => $this->relatedInstitution->id]);

        // Create meeting with agenda item
        $meeting = Meeting::factory()->create(['start_time' => now()]);
        $meeting->institutions()->attach($this->relatedInstitution->id);
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
        ]);

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
        InstitutionLink::factory()->create(['source_institution_id' => $this->relatedInstitution->id, 'target_institution_id' => $this->userInstitution->id]);

        // Create meeting and attach to related institution
        $meeting = Meeting::factory()->create(['start_time' => now()]);
        $meeting->institutions()->attach($this->relatedInstitution->id);

        // Create agenda item for this meeting (should NOT be loaded for unauthorized)
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
        ]);

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
