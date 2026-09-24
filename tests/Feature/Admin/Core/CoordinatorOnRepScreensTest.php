<?php

use App\Actions\GetInstitutionCoordinator;
use App\Actions\GetUserCoordinators;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $role = Role::factory()->create(['guard_name' => 'web']);
    $settings = app(AtstovavimasSettings::class);
    $settings->setInstitutionManagerRoleId($role->id);
    $settings->save();

    $this->managerDuty = Duty::factory()->for($this->institution)->create([
        'name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'],
        'email' => 'koordinatorius@vusa.lt',
    ]);
    $this->managerDuty->roles()->attach($role);
    $this->coordinator = User::factory()->create(['name' => 'Ona Koordinatorė', 'email' => 'ona@gmail.com']);
    $this->coordinator->duties()->attach($this->managerDuty, ['start_date' => now()->subMonth(), 'end_date' => null]);
});

describe('GetInstitutionCoordinator', function (): void {
    test('names the koordinatorius with their duty', function (): void {
        expect(GetInstitutionCoordinator::execute($this->institution))
            ->toMatchArray(['name' => 'Ona Koordinatorė', 'duty' => 'Koordinatorius']);
    });

    test('is written to at the coordinator duty, not a personal or unrelated duty address', function (): void {
        $this->coordinator->duties()->attach(
            Duty::factory()->for($this->institution)->create(['name' => ['lt' => 'Narys', 'en' => 'Member'], 'email' => 'narys@vusa.lt']),
            ['start_date' => now()->subYear(), 'end_date' => null],
        );

        expect(GetInstitutionCoordinator::execute($this->institution))
            ->toMatchArray(['email' => 'koordinatorius@vusa.lt', 'duty' => 'Koordinatorius']);
    });

    test('falls back to their vusa.lt address when the coordinator duty has none', function (): void {
        $this->managerDuty->update(['email' => null]);
        $this->coordinator->duties()->attach(
            Duty::factory()->for($this->institution)->create(['email' => 'ona@vusa.lt']),
            ['start_date' => now()->subYear(), 'end_date' => null],
        );

        expect(GetInstitutionCoordinator::execute($this->institution)['email'])->toBe('ona@vusa.lt');
    });

    test('is never the person asking', function (): void {
        expect(GetInstitutionCoordinator::execute($this->institution, $this->coordinator))->toBeNull();
    });

    test('is null when the tenant has no coordinator', function (): void {
        expect(GetInstitutionCoordinator::execute(Institution::factory()->for(Tenant::factory()->create())->create()))->toBeNull();
    });
});

describe('GetUserCoordinators', function (): void {
    beforeEach(function (): void {
        $this->rep = User::factory()->create();
        $this->seatIn = function (Institution $institution): void {
            $this->rep->duties()->attach(Duty::factory()->for($institution)->create(), ['start_date' => now()->subMonth(), 'end_date' => null]);
        };
    });

    test('names one coordinator per tenant the rep sits in', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->for($otherTenant)->create();
        $otherDuty = Duty::factory()->for($otherInstitution)->create();
        $otherDuty->roles()->attach(app(AtstovavimasSettings::class)->institution_manager_role_id);
        User::factory()->create(['name' => 'Petras Koordinatorius'])
            ->duties()->attach($otherDuty, ['start_date' => now()->subMonth(), 'end_date' => null]);

        ($this->seatIn)($this->institution);
        ($this->seatIn)($otherInstitution);

        expect(collect(GetUserCoordinators::execute($this->rep->fresh()))->pluck('name')->sort()->values()->all())
            ->toBe(['Ona Koordinatorė', 'Petras Koordinatorius']);
    });

    test('lists a shared coordinator once, with every institution they cover', function (): void {
        $secondInstitution = Institution::factory()->for($this->tenant)->create();

        ($this->seatIn)($this->institution);
        ($this->seatIn)($secondInstitution);

        $coordinators = GetUserCoordinators::execute($this->rep->fresh());

        expect($coordinators)->toHaveCount(1)
            ->and($coordinators[0]['institutions'])->toHaveCount(2);
    });
});

describe('the coordinator is one tap away on rep screens (R-g)', function (): void {
    test('the ViSAK overview carries it, deferred', function (): void {
        $rep = makeTenantUserWithRole('Student Representative', $this->tenant);

        asUser($rep)->get(route('dashboard.atstovavimas'))->assertInertia(fn (Assert $page) => $page
            ->missing('coordinators')
            ->loadDeferredProps('secondary', fn (Assert $page) => $page->where('coordinators.0.name', 'Ona Koordinatorė'))
        );
    });

    test('the meeting record leaves the coordinators out, even among its deferred panels', function (): void {
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();

        asUser(makeAdminUser())->get(route('meetings.show', $meeting))->assertInertia(fn (Assert $page) => $page
            ->missing('coordinators')
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page->missing('coordinators'))
        );
    });
});
