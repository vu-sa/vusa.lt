<?php

use App\Enums\InstitutionScope;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionLink;
use App\Models\InstitutionSecretary;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Pivots\AgendaItem;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        asUserWithInertia($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('userInstitutions')
                ->has('user.current_or_upcoming_duties.0.id')
                ->has('user.current_or_upcoming_duties.0.institution_id')
                ->missing('user.current_or_upcoming_duties.0.institution')
                ->has('canViewTenantOverview')
                ->missing('statsTenants')
                ->missing('tenantInstitutions')
                ->missing('representativeActivity')
            );
    });

    test('first paint sends compact institution rows and the initial meetings window', function (): void {
        $this->travelTo('2026-11-21 12:00:00');
        $institution = $this->user->current_or_upcoming_duties()->first()->institution;
        Meeting::factory()->hasAttached($institution)->create(['start_time' => '2024-01-01 12:00:00']);
        $pastCheckIn = InstitutionCheckIn::factory()->for($institution)->for($this->user)->create([
            'start_date' => '2025-01-01', 'end_date' => '2025-01-31',
        ]);
        $activeCheckIn = InstitutionCheckIn::factory()->for($institution)->for($this->user)->create([
            'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'note' => 'No meetings in November.',
        ]);

        asUserWithInertia($this->user)->get(route('dashboard.atstovavimas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('initialMeetingsWindow', ['from' => '2026-07-21', 'to' => '2027-01-21'])
                ->missing('relatedInstitutions')
                ->has('userInstitutions', 1, fn (Assert $row) => $row
                    ->where('id', $institution->id)
                    ->whereType('name', 'string')
                    ->where('tenant.id', $institution->tenant_id)
                    ->where('incomplete_meetings_count', 0)
                    ->where('active_check_in.id', $activeCheckIn->id)
                    ->where('active_check_in.note', 'No meetings in November.')
                    ->where('check_ins', fn ($checkIns) => collect($checkIns)->pluck('id')->contains($pastCheckIn->id))
                    ->has('membership_periods', 1)
                    ->missing('meetings')
                    ->missing('duties')
                    ->etc()));
    });

    test('an active check-in remains on the dashboard at midday on its last day', function (): void {
        $institution = $this->user->current_duties()->first()->institution;
        $checkIn = InstitutionCheckIn::factory()->for($institution)->for($this->user)->create([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        Carbon::setTestNow('2026-11-30 12:00:00');
        try {
            asUserWithInertia($this->user)->getJson(route('dashboard.atstovavimas'))
                ->assertOk()
                ->assertJsonPath('props.userInstitutions.0.active_check_in.id', $checkIn->id)
                ->assertJsonPath('props.userInstitutions.0.active_check_in.end_date', '2026-11-30');
        } finally {
            Carbon::setTestNow();
        }
    });

    test('first-paint query count stays constant as historical incomplete meetings are added', function (): void {
        $institution = $this->admin->current_duties()->first()->institution;
        $addMeeting = function () use ($institution): void {
            $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->subYears(3)]);
            AgendaItem::factory()->for($meeting)->voting()->create();
        };
        $fetch = fn () => asUserWithInertia($this->admin)->getJson(route('dashboard.atstovavimas'))->assertOk();
        $measure = function () use ($fetch): array {
            $fetch();
            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $response = $fetch();

                return [$response, count(DB::getQueryLog())];
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }
        };

        $addMeeting();
        [$small, $smallCount] = $measure();
        $addMeeting();
        $addMeeting();
        [$large, $largeCount] = $measure();

        $small->assertJsonPath('props.userInstitutions.0.incomplete_meetings_count', 1);
        $large->assertJsonPath('props.userInstitutions.0.incomplete_meetings_count', 3);
        expect($largeCount)->toBe($smallCount);
    });

    test('the incomplete count excludes empty agendas and follows outcome votes and joint governance', function (): void {
        $institution = $this->user->current_duties()->first()->institution;
        $internalType = InstitutionType::factory()->create(['extra_attributes' => ['governance_scope' => InstitutionScope::Vusa->value]]);
        $institution->types()->attach($internalType);
        $external = Institution::factory()->for($this->tenant)->create();
        $historicalMeeting = fn () => Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->subYear()]);

        $historicalMeeting();
        AgendaItem::factory()->for($historicalMeeting())->create();
        AgendaItem::factory()->for($historicalMeeting())->deferred()->create();

        $mainWins = AgendaItem::factory()->for($historicalMeeting())->voting()->create();
        Vote::factory()->for($mainWins, 'agendaItem')->main()->create(['decision' => 'positive']);
        Vote::factory()->for($mainWins, 'agendaItem')->additional()->create(['order' => 1]);

        $noMain = AgendaItem::factory()->for($historicalMeeting())->voting()->create();
        Vote::factory()->for($noMain, 'agendaItem')->additional()->create(['decision' => 'positive']);
        Vote::factory()->for($noMain, 'agendaItem')->additional()->create(['order' => 1]);

        $joint = $historicalMeeting();
        $joint->institutions()->attach($external);
        $jointAgendaItem = AgendaItem::factory()->for($joint)->voting()->create();
        Vote::factory()->for($jointAgendaItem, 'agendaItem')->main()->create(['decision' => 'positive']);

        asUserWithInertia($this->user)->getJson(route('dashboard.atstovavimas'))
            ->assertOk()
            ->assertJsonPath('props.userInstitutions.0.incomplete_meetings_count', 3);
    });

    test('followed institutions load with the secondary group, upcoming meetings on first paint', function (): void {
        $followed = Institution::factory()->for($this->tenant)->create();
        $this->admin->followedInstitutions()->attach($followed);
        Meeting::factory()->hasAttached($followed)->create(['start_time' => now()->addDay()]);

        asUserWithInertia($this->admin)
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

    test('open activity requests sent to the rep load with the secondary group', function (): void {
        $institution = $this->user->current_duties()->first()->institution;
        InstitutionActivityRequest::factory()->for($institution)->create(['recipient_id' => $this->user->id]);
        InstitutionActivityRequest::factory()->for($institution)->create();

        asUserWithInertia($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('activityRequests')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('activityRequests', 1)
                    ->where('activityRequests.0.institution_id', (string) $institution->id)
                    ->where('activityRequests.0.recipients_count', 1)
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

        asUserWithInertia($this->user)
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

        asUserWithInertia($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeetings.total', 21)
                ->has('upcomingMeetings.items', 20)
            );
    });

    test('regular user can access atstovavimas dashboard', function (): void {
        asUserWithInertia($this->user)
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
        asUserWithInertia($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', fn ($tenants) => collect($tenants)->every(fn ($tenant) => $tenant['type'] !== 'pkp'))
            );
    });

    test('the padaliniai task number counts padalinys tasks, not the viewer\'s own', function (): void {
        asUserWithInertia($this->admin)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page->missing('openTasksCount'));

        asUserWithInertia(makeAdminUser($this->tenant))
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertInertia(fn (Assert $page) => $page->where('canViewTenantTasks', true));
    });

    test('a padalinys communication coordinator gets statistics and the Gantt for their own padalinys, without the task number', function (): void {
        $ownTenantId = $this->admin->current_duties()->first()->institution->tenant_id;

        asUserWithInertia($this->admin)
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

        asUserWithInertia($coordinator)
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

        asUserWithInertia($this->user)
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

    test('an institution where the user serves as secretary is included in user institutions as secretary', function (): void {
        $secretariedInstitution = Institution::factory()->for($this->tenant)->create();
        $cadence = Cadence::factory()->create(['institution_id' => $secretariedInstitution->id]);
        $this->user->secretariedInstitutions()->attach($secretariedInstitution, ['cadence_id' => $cadence->id]);

        asUserWithInertia($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('userInstitutions', fn ($institutions) => collect($institutions)
                    ->contains(fn ($inst) => data_get($inst, 'id') === $secretariedInstitution->id && data_get($inst, 'is_secretary') === true)
                )
            );
    });

    test('a secretary-only nomination does not add the outgoing linked institution to the dashboard', function (): void {
        $secretary = User::factory()->create();
        $source = Institution::factory()->for($this->tenant)->create();
        $target = Institution::factory()->for($this->tenant)->create();
        $cadence = Cadence::factory()->for($source)->create();
        InstitutionSecretary::create([
            'institution_id' => $source->id,
            'cadence_id' => $cadence->id,
            'user_id' => $secretary->id,
        ]);
        InstitutionLink::factory()->create([
            'source_institution_id' => $source->id,
            'target_institution_id' => $target->id,
        ]);

        asUserWithInertia($secretary)->get(route('dashboard.atstovavimas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('userInstitutions.0.id', $source->id)
                ->where('userInstitutions.0.is_secretary', true)
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload->has('relatedInstitutions', 0)));
    });

    test('a rep who manages no padalinys opens the overview for its Gantt only', function (): void {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $pkpTenant = Tenant::factory()->create(['type' => 'pkp']);
        $ownTenantId = $this->user->current_duties->first()->institution->tenant_id;

        asUserWithInertia($this->user)
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

        asUserWithInertia($globalUser)
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
        asUserWithInertia($this->admin)
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
        asUserWithInertia($superAdmin)
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

        $duty = Duty::factory()->for($institution)->create();
        grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

        // Create a fresh user for this test to avoid interference
        $testUser = User::factory()->create();
        $testUser->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        asUserWithInertia($testUser)
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

        $duty = Duty::factory()->for($institution)->create();
        grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

        // Create a fresh user for this test to avoid interference
        $testUser = User::factory()->create();
        $testUser->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        asUserWithInertia($testUser)
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
        asUserWithInertia($this->admin)
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
        asUserWithInertia($this->user)
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
        asUserWithInertia($this->user)
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
        asUserWithInertia($this->user)
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
        asUserWithInertia($this->user)
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

    test('authorized related institutions return a compact summary on demand', function (): void {
        InstitutionLink::factory()->create([
            'source_institution_id' => $this->userInstitution->id,
            'target_institution_id' => $this->relatedInstitution->id,
        ]);
        $meeting = Meeting::factory()->hasAttached($this->relatedInstitution)->create(['start_time' => now()->subYear()]);
        AgendaItem::factory()->for($meeting)->voting()->create();
        $upcoming = Meeting::factory()->hasAttached($this->relatedInstitution)->create(['start_time' => now()->addDay()]);

        asUserWithInertia($this->user)->get(route('dashboard.atstovavimas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('relatedInstitutions')
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->missing('userInstitutions')
                    ->has('relatedInstitutions', 1, fn (Assert $row) => $row
                        ->where('id', $this->relatedInstitution->id)
                        ->where('authorized', true)
                        ->where('incomplete_meetings_count', 1)
                        ->where('last_meeting_date', $meeting->start_time->toISOString())
                        ->where('next_meeting_date', $upcoming->start_time->toISOString())
                        ->where('activity_status.status', 'covered_by_upcoming_meeting')
                        ->missing('meetings')
                        ->missing('duties')
                        ->etc())));
    });

    test('timeline member rows carry only who held the seat and when', function (): void {
        InstitutionLink::factory()->create(['source_institution_id' => $this->relatedInstitution->id, 'target_institution_id' => $this->userInstitution->id]);
        $former = User::factory()->create(['last_action' => now()]);
        foreach ([$this->relatedInstitution, $this->userInstitution] as $institution) {
            Duty::factory()->for($institution)->create()->users()->attach($former->id, [
                'start_date' => '2020-07-01', 'end_date' => '2021-06-30',
                'additional_email' => 'private@vusa.lt', 'study_program_note' => 'Private note',
            ]);
        }

        $hasFormerMember = fn ($institutions): bool => collect($institutions)
            ->flatMap(fn (array $institution) => $institution['membership_periods'])
            ->contains(fn (array $period): bool => array_keys($period) === ['user', 'duty_id', 'start_date', 'end_date']
                && $period['user'] === ['id' => $former->id, 'name' => $former->name, 'profile_photo_path' => $former->profile_photo_path]
                && $period['start_date'] === '2020-07-01'
                && $period['end_date'] === '2021-06-30');

        asUserWithInertia($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('userInstitutions', $hasFormerMember)
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->where('relatedInstitutions', $hasFormerMember)
                )
            );
    });

    test('restricted related institution summaries keep the dates and hide recording and activity status', function (): void {
        $this->travelTo('2026-11-21 12:00:00');
        InstitutionLink::factory()->create([
            'source_institution_id' => $this->relatedInstitution->id,
            'target_institution_id' => $this->userInstitution->id,
            'mutual' => false,
        ]);
        $meeting = Meeting::factory()->hasAttached($this->relatedInstitution)->create(['start_time' => now()->subYear()]);
        AgendaItem::factory()->for($meeting)->voting()->create();
        $upcoming = Meeting::factory()->hasAttached($this->relatedInstitution)->create(['start_time' => now()->addDay()]);
        $checkIn = InstitutionCheckIn::factory()->for($this->relatedInstitution)->for($this->user)->create();

        asUserWithInertia($this->user)->get(route('dashboard.atstovavimas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->reloadOnly('relatedInstitutions', fn (Assert $reload) => $reload
                    ->has('relatedInstitutions', 1, fn (Assert $row) => $row
                        ->where('id', $this->relatedInstitution->id)
                        ->where('authorized', false)
                        ->where('incomplete_meetings_count', null)
                        ->where('last_meeting_date', $meeting->start_time->toISOString())
                        ->where('next_meeting_date', $upcoming->start_time->toISOString())
                        ->where('last_activity_at', $checkIn->created_at->toISOString())
                        ->where('activity_status', null)
                        ->missing('meetings')
                        ->missing('duties')
                        ->etc())));
    });
});
