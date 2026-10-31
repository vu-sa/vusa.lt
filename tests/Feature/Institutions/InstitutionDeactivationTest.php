<?php

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\CollapseOverlappingDutiables;
use App\Actions\Dutiables\MergeDutiables;
use App\Actions\GetUserAccessSummary;
use App\Actions\MergeDuties;
use App\Actions\UpdateInstitution;
use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Services\InstitutionDeactivationService;
use App\Services\ModelAuthorizer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-16 12:00:00');
    config(['queue.default' => 'database']);
});

function closeInstitutionMemberships(Institution $institution): User
{
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $institution->tenant);
    app(UpdateInstitution::class)->execute($institution, ['is_active' => false], $actor);

    return $actor;
}

test('deactivation closes current and delegated terms without changing past terms or duty definitions', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $current = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $scheduledEnd = Dutiable::factory()->forDuty($duty)->create(['start_date' => '2026-02-01', 'end_date' => '2026-12-31']);
    $delegated = Dutiable::factory()->forDuty($duty)->forTenant(Tenant::main())->active()->create(['start_date' => '2026-03-01']);
    $past = Dutiable::factory()->forDuty($duty)->create(['start_date' => '2025-01-01', 'end_date' => '2026-05-01']);
    $holder = $current->user;
    $holder->assignRole('Išteklių administratorius');
    $unrelated = Dutiable::factory()->forUser($holder)->active()->create(['start_date' => '2026-01-01']);

    $actor = closeInstitutionMemberships($institution);

    foreach ([$current, $scheduledEnd, $delegated] as $term) {
        expect($term->fresh()->end_date->toDateString())->toBe('2026-10-15')
            ->and($term->fresh()->ended_by_institution_deactivation)->toBeTrue();
    }
    expect($institution->fresh()->is_active)->toBe(0)
        ->and($duty->fresh()->is_active)->toBeTrue()
        ->and($past->fresh()->end_date->toDateString())->toBe('2026-05-01')
        ->and($past->fresh()->ended_by_institution_deactivation)->toBeFalse()
        ->and($unrelated->fresh()->end_date)->toBeNull()
        ->and($holder->fresh()->hasRole('Išteklių administratorius'))->toBeTrue();
    $audit = Activity::forSubject($institution)->where('description', 'institution_deactivated')->sole();
    expect($audit->causer_id)->toBe($actor->id)
        ->and($audit->properties['dutiable_ids'])->toEqualCanonicalizing([$current->id, $scheduledEnd->id, $delegated->id]);
});

test('same-day and future terms block the whole update without altering assignments', function (string $start): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $current = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $blocker = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => $start]);
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $institution->tenant);

    asUser($actor)->patch(route('institutions.update', $institution), [
        'name' => ['lt' => 'Institucija', 'en' => 'Institution'],
        'tenant_id' => $institution->tenant_id,
        'is_active' => false,
    ])->assertSessionHasErrors(['is_active' => __('forms.helpers.institution_deactivation_blocked')]);

    expect($institution->fresh()->is_active)->toBe(1)
        ->and($current->fresh()->end_date)->toBeNull()
        ->and($blocker->fresh()->end_date)->toBeNull();
})->with(['today' => '2026-10-16', 'future' => '2026-11-01']);

test('the transactional action rechecks blockers added after form validation', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    expect(app(InstitutionDeactivationService::class)->summary($institution)['blockers'])->toBeEmpty();
    Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-11-01']);

    expect(fn () => closeInstitutionMemberships($institution))->toThrow(ValidationException::class)
        ->and($institution->fresh()->is_active)->toBe(1);
});

test('unauthorized deactivation leaves assignments current', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create();

    asUser(makeUser(Tenant::main()))->patch(route('institutions.update', $institution), [
        'name' => ['lt' => 'Institucija', 'en' => 'Institution'], 'tenant_id' => $institution->tenant_id, 'is_active' => false,
    ])->assertForbidden();

    expect($institution->fresh()->is_active)->toBe(1)->and($term->fresh()->end_date)->toBeNull();
});

test('incoming and outgoing ex officio seats close while unrelated source terms remain current', function (): void {
    config(['queue.default' => 'sync']);
    $institution = Institution::factory()->create();
    $ownDuty = Duty::factory()->for($institution)->create();
    $externalSource = Duty::factory()->create();
    $externalTarget = Duty::factory()->create();
    $externalSource->exOfficioTargetDuties()->attach($ownDuty);
    $ownDuty->exOfficioTargetDuties()->attach($externalTarget);
    $incomingSource = Dutiable::factory()->forDuty($externalSource)->active()->create(['start_date' => '2026-01-01']);
    $ownSource = Dutiable::factory()->forDuty($ownDuty)->active()->create(['start_date' => '2026-01-01']);
    $incoming = Dutiable::where('via_dutiable_id', $incomingSource->id)->sole();
    $outgoing = Dutiable::where('via_dutiable_id', $ownSource->id)->sole();

    $actor = closeInstitutionMemberships($institution);
    app(UpdateInstitution::class)->execute($institution, ['is_active' => true], $actor);
    $incomingSource->save();
    $ownSource->fresh()->save();
    ReconcileExOfficioSeats::forDuty($externalSource);

    expect($incomingSource->fresh()->end_date)->toBeNull();
    foreach ([$incoming, $outgoing, $ownSource] as $term) {
        expect($term->fresh()->end_date->toDateString())->toBe('2026-10-15')
            ->and($term->fresh()->ended_by_institution_deactivation)->toBeTrue();
    }
});

test('ex officio sync and backfill skip inactive target institutions', function (): void {
    config(['queue.default' => 'sync']);
    $inactive = Institution::factory()->create(['is_active' => false]);
    $sourceDuty = Duty::factory()->create();
    $targetDuty = Duty::factory()->for($inactive)->create();
    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);
    $source = Dutiable::factory()->forDuty($sourceDuty)->active()->create(['start_date' => '2026-01-01']);

    ReconcileExOfficioSeats::forDuty($sourceDuty);

    expect(Dutiable::where('via_dutiable_id', $source->id)->exists())->toBeFalse();
});

test('automatic seats are not created from legacy memberships in an inactive institution', function (): void {
    $institution = Institution::factory()->create();
    $sourceDuty = Duty::factory()->for($institution)->create();
    $targetDuty = Duty::factory()->create();
    $source = Dutiable::factory()->forDuty($sourceDuty)->active()->create(['start_date' => '2026-01-01']);
    $institution->update(['is_active' => false]);
    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    ReconcileExOfficioSeats::forDuty($sourceDuty);
    $source->save();

    expect(Dutiable::where('via_dutiable_id', $source->id)->exists())->toBeFalse()
        ->and($source->fresh()->end_date)->toBeNull();
});

test('reactivation and repeated saves preserve ended periods and returning members get new periods', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $user = User::factory()->create();
    $term = Dutiable::factory()->forDuty($duty)->forUser($user)->active()->create(['start_date' => '2026-01-01']);
    $actor = closeInstitutionMemberships($institution);

    app(UpdateInstitution::class)->execute($institution, ['is_active' => false], $actor);
    app(UpdateInstitution::class)->execute($institution, ['is_active' => true], $actor);
    app(DutyAssignmentService::class)->syncRepresentatives($duty, [$user->id]);

    expect($term->fresh()->end_date->toDateString())->toBe('2026-10-15')
        ->and($duty->dutiables()->current()->sole()->id)->not->toBe($term->id)
        ->and($duty->dutiables()->current()->sole()->start_date->toDateString())->toBe('2026-10-16')
        ->and(Activity::forSubject($institution)->where('description', 'institution_deactivated')->count())->toBe(1);
});

test('inactive institutions permit historical records but reject new current or future terms', function (string $start, ?string $end, bool $allowed): void {
    $institution = Institution::factory()->create(['is_active' => false]);
    $duty = Duty::factory()->for($institution)->create();
    $create = fn () => Dutiable::factory()->forDuty($duty)->create(['start_date' => $start, 'end_date' => $end]);

    if ($allowed) {
        expect($create()->exists)->toBeTrue();
    } else {
        expect($create)->toThrow(ValidationException::class)
            ->and($duty->dutiables()->exists())->toBeFalse();
    }
})->with([
    'historical' => ['2026-01-01', '2026-10-15', true],
    'current' => ['2026-01-01', null, false],
    'last day today' => ['2026-01-01', '2026-10-16', false],
    'future' => ['2026-11-01', null, false],
]);

test('stale models and timeline edits cannot reopen a closed period after reactivation', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $stale = $term->fresh();
    $actor = closeInstitutionMemberships($institution);
    app(UpdateInstitution::class)->execute($institution, ['is_active' => true], $actor);

    $stale->update(['additional_email' => 'updated@example.com']);
    expect($stale->fresh()->end_date->toDateString())->toBe('2026-10-15')
        ->and(fn () => $term->fresh()->update(['end_date' => null]))->toThrow(ValidationException::class);

    $newTerm = Dutiable::factory()->forDuty($duty)->forUser($term->user)->active()->create(['start_date' => '2026-10-16']);
    expect(MergeDutiables::isMergeable(Dutiable::whereKey([$term->id, $newTerm->id])->get()))->toBeFalse()
        ->and(fn () => CollapseOverlappingDutiables::foldInto($term->fresh(), $newTerm))->toThrow(ValidationException::class);
});

test('a failure after terms close rolls back the institution and assignments together', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $institution->tenant);

    expect(fn () => app(UpdateInstitution::class)->execute($institution, ['is_active' => false, 'types' => [999999]], $actor))
        ->toThrow(QueryException::class)
        ->and($institution->fresh()->is_active)->toBe(1)
        ->and($term->fresh()->end_date)->toBeNull()
        ->and($term->fresh()->ended_by_institution_deactivation)->toBeFalse();
});

test('closed assignments remain in both user history surfaces with their institution and dates', function (): void {
    $institution = Institution::factory()->create(['name' => ['lt' => 'Buvusi institucija', 'en' => 'Former institution']]);
    $duty = Duty::factory()->for($institution)->create();
    $user = makeUser($institution->tenant);
    $term = Dutiable::factory()->forDuty($duty)->forUser($user)->active()->create(['start_date' => '2026-01-01']);
    $actor = closeInstitutionMemberships($institution);

    asUser($actor)->get(route('users.show', $user))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('user.previous_duties.0.id', $duty->id)
        ->where('user.previous_duties.0.institution.name', 'Buvusi institucija')
    );
    $summary = GetUserAccessSummary::execute($user->fresh());
    expect(collect($summary['ended'])->firstWhere('id', $term->id))
        ->toMatchArray(['institutionName' => 'Buvusi institucija', 'institutionIsActive' => false, 'startDate' => '2026-01-01', 'endDate' => '2026-10-15']);
    asUser($user)->get(route('profile.roles'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('access.ended.0.institutionName', 'Buvusi institucija')
        ->where('access.ended.0.endDate', '2026-10-15')
    );
});

test('deactivation immediately invalidates warmed permissions even with a queued listener', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $duty->assignRole('Studentų atstovas');
    $user = User::factory()->create();
    Dutiable::factory()->forDuty($duty)->forUser($user)->active()->create(['start_date' => '2026-01-01']);
    $authorizer = app(ModelAuthorizer::class);
    expect($authorizer->allows($user, 'meetings.create.own'))->toBeTrue();

    closeInstitutionMemberships($institution);

    expect($authorizer->allows($user, 'meetings.create.own'))->toBeFalse();
});

test('moving an occupied duty into an inactive institution is rejected', function (): void {
    $duty = Duty::factory()->create();
    Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $inactive = Institution::factory()->create(['is_active' => false]);
    $originalId = $duty->institution_id;

    expect(fn () => $duty->update(['institution_id' => $inactive->id]))->toThrow(ValidationException::class)
        ->and($duty->fresh()->institution_id)->toBe($originalId);
});

test('merging duties cannot move current assignments into an inactive institution', function (): void {
    $source = Duty::factory()->create();
    $term = Dutiable::factory()->forDuty($source)->active()->create(['start_date' => '2026-01-01']);
    $inactive = Institution::factory()->create(['is_active' => false]);
    $target = Duty::factory()->for($inactive)->create();

    expect(fn () => MergeDuties::execute($target, collect([$source])))->toThrow(ValidationException::class)
        ->and($term->fresh()->duty_id)->toBe($source->id)
        ->and($source->fresh()->trashed())->toBeFalse();
});

test('deactivation includes assignments on soft deleted duties', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    $duty->delete();

    closeInstitutionMemberships($institution);

    expect($term->fresh()->end_date->toDateString())->toBe('2026-10-15')
        ->and($term->fresh()->ended_by_institution_deactivation)->toBeTrue();
});
