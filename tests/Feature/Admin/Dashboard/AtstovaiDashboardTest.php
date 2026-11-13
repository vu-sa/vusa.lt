<?php

use App\Enums\InstitutionActivityStatus;
use App\Enums\LearningImportance;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\LearningStep;
use App\Models\Meeting;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->freezeTime();
    [$this->tenant, $this->otherTenant] = Tenant::query()->representational()->take(2)->get()->all();
    $this->duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    grantPurpose($this->duty, ResponsibilityPurpose::StudentRepresentation);
    $this->otherDuty = Duty::factory()->for(Institution::factory()->for($this->otherTenant))->create();
    grantPurpose($this->otherDuty, ResponsibilityPurpose::StudentRepresentation);

    $this->rep = User::factory()->create(['last_action' => now()->subDay()]);
    Dutiable::factory()->forUser($this->rep)->forDuty($this->duty)->create(['start_date' => today()->subMonths(2), 'end_date' => null]);
    $this->outsider = User::factory()->create(['last_action' => now()->subDay()]);
    Dutiable::factory()->forUser($this->outsider)->forDuty($this->otherDuty)->create(['start_date' => today()->subMonths(2), 'end_date' => null]);

    LearningStep::query()->update(['is_active' => false]);
    LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['importance' => LearningImportance::Essential])->create();
});

function padalinysManager(Tenant $tenant): User
{
    $manager = makeUser($tenant);
    $role = Role::firstOrCreate(['name' => 'Atstovai padalinio skaitytojas', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']));
    $manager->current_duties()->firstOrFail()->assignRole($role);

    return $manager;
}

function repCoordinator(Tenant $tenant): User
{
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);

    return $coordinator;
}

/** @return array<string, array<string, mixed>> */
function loadedReps(User $viewer, array $query = []): array
{
    $reps = [];
    asUser($viewer)->get(route('representatives.index', $query))->assertOk()->assertInertia(function (Assert $page) use (&$reps): void {
        $page->component('Admin/Dashboard/ShowAtstovai')
            ->missing('overview')
            ->loadDeferredProps('reps', function (Assert $page) use (&$reps): void {
                $reps = collect($page->toArray()['props']['overview']['reps'])->keyBy('id')->all();
            });
    });

    return $reps;
}

describe('unauthorized access', function (): void {
    test('a member without padalinys rights or coordination gets 403', function (): void {
        $member = makeUser($this->tenant);

        asUser($member)->get(route('representatives.index'))->assertForbidden();
        asUser($member)->get(route('representatives.show', $this->rep))->assertForbidden();
    });
});

describe('authorized access', function (): void {
    test('a padalinys manager sees the padalinys reps without their learning progress', function (): void {
        $manager = padalinysManager($this->tenant);

        asUser($manager)->get(route('representatives.index'))->assertInertia(fn (Assert $page) => $page
            ->where('selectedTenantIds', [(string) $this->tenant->id])
            ->has('tenants', 1));
        $reps = loadedReps($manager);

        expect($reps)->toHaveKey($this->rep->id)->not->toHaveKey($this->outsider->id)
            ->and($reps[$this->rep->id]['learning'])->toBeNull();
    });

    test('a coordinator without padalinys rights sees only the reps they coordinate, with progress', function (): void {
        $coordinator = repCoordinator($this->tenant);

        asUser($coordinator)->get(route('representatives.index'))->assertInertia(fn (Assert $page) => $page
            ->where('tenants', [])->where('selectedTenantIds', []));
        $reps = loadedReps($coordinator);

        expect($reps)->toHaveKey($this->rep->id)->not->toHaveKey($this->outsider->id)
            ->and($reps[$this->rep->id]['learning'])->toMatchArray(['essentialDone' => 0, 'essentialTotal' => 1]);
    });

    test('the rep page shows the path only to their coordinator', function (): void {
        asUser(padalinysManager($this->tenant))->get(route('representatives.show', $this->rep))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Representation/ShowRepresentative')
                ->where('rep.id', $this->rep->id)->where('path', null)->where('rep.learning', null));

        asUser(repCoordinator($this->tenant))->get(route('representatives.show', $this->rep))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('path.totals')->where('rep.learning.essentialTotal', 1));
    });

    test('old Mano kelias links land on the new pages', function (): void {
        $coordinator = repCoordinator($this->tenant);

        asUser($coordinator)->get('/mano/learning/reps')->assertRedirect(route('representatives.index'))->assertStatus(301);
        asUser($coordinator)->get("/mano/learning/reps/{$this->rep->id}")->assertRedirect(route('representatives.show', $this->rep));
    });
});

describe('attention', function (): void {
    test('each rep gets one reason in priority order and incoming reps get none', function (): void {
        $make = function (array $attributes, $start) {
            $user = User::factory()->create($attributes);
            Dutiable::factory()->forUser($user)->forDuty($this->duty)->create(['start_date' => $start, 'end_date' => null]);

            return $user;
        };
        $never = $make(['last_action' => null], today()->subMonth());
        $inactive = $make(['last_action' => now()->subDays(40)], today()->subWeek());
        $incoming = $make(['last_action' => null], today()->addMonth());

        $reps = loadedReps(repCoordinator($this->tenant));

        expect($reps[$never->id]['attention']['reason'])->toBe('never_logged_in')
            ->and($reps[$this->rep->id]['attention']['reason'])->toBe('essentials_not_started')
            ->and($reps[$inactive->id]['attention']['reason'])->toBe('inactive')
            ->and($reps[$incoming->id]['attention'])->toBeNull()
            ->and($reps[$incoming->id]['incoming'])->toBeTrue()
            ->and(array_keys($reps))->toBe([$never->id, $this->rep->id, $inactive->id, $incoming->id]);
    });

    test('an overdue institution outranks untouched essentials', function (): void {
        $this->duty->institution->meetings()->attach(Meeting::factory()->create(['start_time' => now()->subYear()]));

        $rep = loadedReps(repCoordinator($this->tenant))[$this->rep->id];

        expect($rep['institutions'][0]['status'])->toBe(InstitutionActivityStatus::Overdue->value)
            ->and($rep['attention'])->toBe(['reason' => 'institution_overdue', 'institution' => $this->duty->institution->getTranslation('name', 'lt')]);
    });
});

describe('tenant isolation', function (): void {
    test('a padalinys manager cannot pick another padalinys or open its reps', function (): void {
        $manager = padalinysManager($this->tenant);

        asUser($manager)->get(route('representatives.index', ['tenants' => [$this->otherTenant->id]]))
            ->assertInertia(fn (Assert $page) => $page->where('selectedTenantIds', [(string) $this->tenant->id]));
        asUser($manager)->get(route('representatives.show', $this->outsider))->assertForbidden();
    });
});
