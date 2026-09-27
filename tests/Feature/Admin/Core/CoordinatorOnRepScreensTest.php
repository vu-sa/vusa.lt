<?php

use App\Actions\GetInstitutionCoordinators;
use App\Actions\GetUserCoordinators;
use App\Enums\InstitutionScope;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->managerDuty = Duty::factory()->for($this->institution)->create([
        'name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'],
        'email' => 'koordinatorius@vusa.lt',
    ]);
    DutyResponsibility::factory()->for($this->managerDuty)->forTenant($this->tenant)->create();
    $this->coordinator = User::factory()->create(['name' => 'Ona Koordinatorė', 'email' => 'ona@gmail.com']);
    $this->coordinator->duties()->attach($this->managerDuty, ['start_date' => now()->subMonth(), 'end_date' => null]);
});

/** The card a rep sees for one institution, or null. */
function coordinatorCardFor(Institution $institution, ?User $except = null): ?array
{
    return GetInstitutionCoordinators::execute([$institution], $except)[0] ?? null;
}

describe('GetInstitutionCoordinators', function (): void {
    test('names the koordinatorius with their duty', function (): void {
        expect(coordinatorCardFor($this->institution))
            ->toMatchArray(['name' => 'Ona Koordinatorė', 'duty' => 'Koordinatorius']);
    });

    test('is written to at the coordinator duty, not a personal or unrelated duty address', function (): void {
        $this->coordinator->duties()->attach(
            Duty::factory()->for($this->institution)->create(['name' => ['lt' => 'Narys', 'en' => 'Member'], 'email' => 'narys@vusa.lt']),
            ['start_date' => now()->subYear(), 'end_date' => null],
        );

        expect(coordinatorCardFor($this->institution))
            ->toMatchArray(['email' => 'koordinatorius@vusa.lt', 'duty' => 'Koordinatorius']);
    });

    test('falls back to their vusa.lt address when the coordinator duty has none', function (): void {
        $this->managerDuty->update(['email' => null]);
        $this->coordinator->duties()->attach(
            Duty::factory()->for($this->institution)->create(['email' => 'ona@vusa.lt']),
            ['start_date' => now()->subYear(), 'end_date' => null],
        );

        expect(coordinatorCardFor($this->institution)['email'])->toBe('ona@vusa.lt');
    });

    test('is never the person asking', function (): void {
        expect(coordinatorCardFor($this->institution, $this->coordinator))->toBeNull();
    });

    test('is null for a body that is not a VU body', function (InstitutionScope $scope): void {
        $this->institution->types()->attach(Type::factory()->forInstitutions($scope)->create());

        expect(coordinatorCardFor($this->institution->fresh()))->toBeNull();
    })->with(['VU SA body' => InstitutionScope::Vusa, 'national body' => InstitutionScope::National, 'international body' => InstitutionScope::International]);

    test('names whoever coordinates the institution\'s type instead of the padalinys coordinator', function (): void {
        $senate = Type::factory()->forInstitutions()->create();
        $this->institution->types()->attach($senate);
        $centralDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create(['name' => ['lt' => 'CB koordinatorius', 'en' => 'CB coordinator']]);
        DutyResponsibility::factory()->for($centralDuty)->forType($senate)->create();
        User::factory()->create(['name' => 'Jonas Centras'])->duties()->attach($centralDuty, ['start_date' => now()->subMonth()]);

        expect(coordinatorCardFor($this->institution->fresh()))->toMatchArray(['name' => 'Jonas Centras', 'duty' => 'CB koordinatorius']);
    });

    test('is null when the tenant has no coordinator', function (): void {
        expect(coordinatorCardFor(Institution::factory()->for(Tenant::factory()->create())->create()))->toBeNull();
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
        DutyResponsibility::factory()->for($otherDuty)->forTenant($otherTenant)->create();
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

    test('leaves a VU SA body out of what the coordinator covers', function (): void {
        $vusaType = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
        $board = Institution::factory()->for($this->tenant)->create(['name' => ['lt' => 'Valdyba', 'en' => 'Board']]);
        $board->types()->attach($vusaType);

        ($this->seatIn)($board);
        ($this->seatIn)($this->institution);

        $coordinators = GetUserCoordinators::execute($this->rep->fresh());

        expect($coordinators)->toHaveCount(1)
            ->and($coordinators[0]['institutions'])->toBe([$this->institution->name]);
    });

    test('names nobody to a rep who sits only in VU SA bodies', function (): void {
        $vusaType = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
        $this->institution->types()->attach($vusaType);

        ($this->seatIn)($this->institution);

        expect(GetUserCoordinators::execute($this->rep->fresh()))->toBeEmpty();
    });

    test('never names the coordinator as their own coordinator', function (): void {
        expect(GetUserCoordinators::execute($this->coordinator->fresh()))->toBeEmpty();
    });

    test('names nobody while the padalinys has no coordinator', function (): void {
        $this->managerDuty->responsibilities()->each(fn (DutyResponsibility $assignment) => $assignment->delete());
        ($this->seatIn)($this->institution);

        expect(GetUserCoordinators::execute($this->rep->fresh()))->toBeEmpty();
    });
});

describe('the coordinator is one tap away on rep screens (R-g)', function (): void {
    test('the ViSAK overview carries it, deferred', function (): void {
        $rep = makeTenantUserWithRole('Studentų atstovas', $this->tenant);

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
