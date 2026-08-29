<?php

use App\Actions\GetInstitutionAdministrators;
use App\Actions\ResolveTaskAssignees;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionAdministrator;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole(config('permission.super_admin_role_name'));
    $this->outsider = makeUser($this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);
    $this->candidate = User::factory()->create();
});

describe('the roster endpoint', function (): void {
    test('nominates administrators for a term', function (): void {
        asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $this->cadence->id,
            'user_ids' => [$this->candidate->id],
        ])->assertRedirect();

        expect($this->institution->administrators()->pluck('users.id')->all())
            ->toBe([$this->candidate->id]);
    });

    test('replaces the roster wholesale, term by term', function (): void {
        $other = User::factory()->create();
        $secondTerm = Cadence::factory()->forYear(2026)->create(['institution_id' => $this->institution->id]);

        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $this->cadence->id,
            'user_id' => $this->candidate->id,
        ]);
        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $secondTerm->id,
            'user_id' => $this->candidate->id,
        ]);

        asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $this->cadence->id,
            'user_ids' => [$other->id],
        ])->assertRedirect();

        expect($this->institution->administratorAssignments()->where('cadence_id', $this->cadence->id)->pluck('user_id')->all())
            ->toBe([$other->id])
            // The other term is untouched.
            ->and($this->institution->administratorAssignments()->where('cadence_id', $secondTerm->id)->pluck('user_id')->all())
            ->toBe([$this->candidate->id]);
    });

    test('an empty roster clears the term', function (): void {
        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $this->cadence->id,
            'user_id' => $this->candidate->id,
        ]);

        asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $this->cadence->id,
            'user_ids' => [],
        ])->assertRedirect();

        expect($this->institution->administrators()->count())->toBe(0);
    });
});

describe('authorization', function (): void {
    test('a user who cannot update the institution is forbidden', function (): void {
        asUser($this->outsider)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $this->cadence->id,
            'user_ids' => [$this->candidate->id],
        ])->assertForbidden();

        expect(InstitutionAdministrator::count())->toBe(0);
    });

    test('a cadence belonging to another institution is rejected', function (): void {
        $otherInstitution = Institution::factory()->for($this->tenant)->create();
        $foreignCadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $otherInstitution->id]);

        asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $foreignCadence->id,
            'user_ids' => [$this->candidate->id],
        ])->assertSessionHasErrors('cadence_id');

        expect(InstitutionAdministrator::count())->toBe(0);
    });

    test('an institution with its own terms cannot staff a global one', function (): void {
        $globalCadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
            'cadence_id' => $globalCadence->id,
            'user_ids' => [$this->candidate->id],
        ])->assertSessionHasErrors('cadence_id');
    });

    test('an institution without its own terms staffs the global ladder', function (): void {
        $bare = Institution::factory()->for($this->tenant)->create();
        $globalCadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->put(route('institutions.administrators.update', $bare), [
            'cadence_id' => $globalCadence->id,
            'user_ids' => [$this->candidate->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($bare->administrators()->count())->toBe(1);
    });
});

describe('resolving administrators for a date', function (): void {
    beforeEach(function (): void {
        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $this->cadence->id,
            'user_id' => $this->candidate->id,
        ]);
    });

    test('a date inside the term resolves to its administrators', function (): void {
        expect(GetInstitutionAdministrators::execute($this->institution, Carbon::parse('2025-11-01'))->pluck('id')->all())
            ->toBe([$this->candidate->id]);
    });

    test('a date outside every term resolves to nobody', function (): void {
        // The fallback to date-scoped members is what stops a nomination made today
        // from being applied retroactively to a sitting held years earlier.
        expect(GetInstitutionAdministrators::execute($this->institution, Carbon::parse('2019-11-01')))
            ->toBeEmpty();
    });
});

describe('task assignment', function (): void {
    beforeEach(function (): void {
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

        $duty = Duty::factory()->for($this->institution)->hasAttached($studentRepType, [], 'types')->create();

        // An open-ended dutiable: the shape that made one long-standing member match
        // every meeting the body has ever held.
        $this->member = User::factory()->create();
        $this->member->duties()->attach($duty, ['start_date' => '2019-01-01', 'end_date' => null]);

        $this->meeting = Meeting::factory()->hasAttached($this->institution)->create([
            'start_time' => '2025-11-01 10:00:00',
        ]);
    });

    test('falls back to the members active at the meeting date', function (): void {
        expect(ResolveTaskAssignees::forMeeting($this->meeting)->pluck('id')->all())
            ->toBe([$this->member->id]);
    });

    test('administrators replace the membership once nominated', function (): void {
        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $this->cadence->id,
            'user_id' => $this->candidate->id,
        ]);

        expect(ResolveTaskAssignees::forMeeting($this->meeting->fresh())->pluck('id')->all())
            ->toBe([$this->candidate->id]);
    });

    test('a meeting outside every term still goes to the members active then', function (): void {
        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $this->cadence->id,
            'user_id' => $this->candidate->id,
        ]);

        $oldMeeting = Meeting::factory()->hasAttached($this->institution)->create([
            'start_time' => '2021-03-01 10:00:00',
        ]);

        expect(ResolveTaskAssignees::forMeeting($oldMeeting)->pluck('id')->all())
            ->toBe([$this->member->id]);
    });
});
