<?php

use App\Actions\CollapseOverlappingDutiables;
use App\Actions\Dutiables\ApplyExOfficioReview;
use App\Actions\Dutiables\PlanExOfficioReview;
use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Actions\MergeDuties;
use App\Actions\UpdateDuty;
use App\Actions\UpdateInstitution;
use App\Events\DutiableChanged;
use App\Events\SearchRelationChanged;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\AccessChangeAnalyzer;
use App\Services\ResourceServices\UserDutyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->sourceDuty = Duty::factory()->for($this->institution)->create();
    $this->targetDuty = Duty::factory()->for($this->institution)->create();
    $this->holder = User::factory()->create();
});

function reconcilerSource(array $attributes = []): Dutiable
{
    return Dutiable::factory()->forDuty(test()->sourceDuty)->forUser(test()->holder)->create([
        'start_date' => '2026-01-01', 'end_date' => null, ...$attributes,
    ]);
}

test('reopened target resumes today and preserves the closed seat exactly', function (): void {
    $targetInstitution = Institution::factory()->for($this->tenant)->create();
    $this->targetDuty->update(['institution_id' => $targetInstitution->id]);
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource();
    $closed = $source->derivedDutiables()->sole();
    $actor = makeAdminUser($this->tenant);
    app(UpdateInstitution::class)->execute($targetInstitution, ['is_active' => false], $actor);
    $snapshot = $closed->fresh()->getAttributes();
    $this->travelTo(today()->addDays(5));
    app(UpdateInstitution::class)->execute($targetInstitution, ['is_active' => true], $actor);

    expect($closed->fresh()->getAttributes())->toBe($snapshot)
        ->and($source->derivedDutiables()->current()->sole()->start_date->toDateString())->toBe('2026-11-04');
});

test('editing an ended source never invents history but explicit review can create it', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource(['end_date' => '2026-06-30']);
    $source->update(['start_date' => '2026-02-01']);
    expect($source->derivedDutiables()->exists())->toBeFalse();
    $plan = PlanExOfficioReview::forDuty($this->sourceDuty);
    expect($plan['items'][0])->toMatchArray(['kind' => 'create', 'historical' => true, 'selected' => false]);
    ReconcileExOfficioSeats::forSource($source, includeHistory: true, targetId: $this->targetDuty->id);
    expect($source->derivedDutiables()->sole()->end_date->toDateString())->toBe('2026-06-30');
});

test('removing a target tenant clears seat tenant without ending it', function (): void {
    $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $institution = Institution::factory()->for($other)->create();
    $this->targetDuty->update(['institution_id' => $institution->id]);
    $this->targetDuty->assignableTenants()->attach($this->tenant);
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    expect($seat->tenant_id)->toBe($this->tenant->id);
    app(UpdateDuty::class)->execute($this->targetDuty, ['institution_id' => $institution->id, 'assignable_tenants' => []]);

    expect($seat->fresh()->tenant_id)->toBeNull()->and($seat->fresh()->end_date)->toBeNull();
});

test('moving a target institution reconciles seats without ending them', function (): void {
    $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $institution = Institution::factory()->for($other)->create();
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    app(UpdateDuty::class)->execute($this->targetDuty, ['institution_id' => $institution->id,
        'assignable_tenants' => [['tenant_id' => $this->tenant->id]]]);

    expect($seat->fresh()->tenant_id)->toBe($this->tenant->id)->and($seat->fresh()->end_date)->toBeNull();
});

test('paused restores its nested state after an exception', function (): void {
    expect(fn () => ReconcileExOfficioSeats::paused(function (): void {
        ReconcileExOfficioSeats::paused(fn () => throw new RuntimeException('pause'));
    }))->toThrow(RuntimeException::class);
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    expect(reconcilerSource()->derivedDutiables()->count())->toBe(1);
});

test('merge preserves closure and past seats and logs their old source', function (): void {
    $duplicate = Duty::factory()->for($this->institution)->create();
    foreach ([$duplicate, $this->sourceDuty] as $duty) {
        $duty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    }
    $survivor = reconcilerSource();
    $loser = Dutiable::factory()->forDuty($duplicate)->forUser($this->holder)->create(['start_date' => '2026-02-01', 'end_date' => null]);
    $closed = $loser->derivedDutiables()->sole();
    $closed->forceFill(['end_date' => '2026-03-31', 'ended_by_institution_deactivation' => true])->save();
    $past = $survivor->derivedDutiables()->sole();
    $past->update(['end_date' => '2026-01-31', 'additional_email' => 'past@example.com']);
    MergeDuties::execute($this->sourceDuty, collect([$duplicate]));

    expect($closed->fresh()->ended_by_institution_deactivation)->toBeTrue()
        ->and($closed->fresh()->end_date->toDateString())->toBe('2026-03-31')
        ->and($closed->fresh()->via_dutiable_id)->toBe($survivor->id)
        ->and($past->fresh())->not->toBeNull()
        ->and(Activity::forSubject($closed)->where('description', 'ex_officio_source_repointed')->first()->properties['old']['via_dutiable_id'])->toBe($loser->id);
});

test('self lockout sees role loss through a derived seat and rolls everything back', function (): void {
    $role = Role::findByName('Komunikacijos koordinatorius');
    $this->targetDuty->assignRole($role);
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $report = app(AccessChangeAnalyzer::class)->apply($this->holder,
        fn () => $source->update(['end_date' => today()->subDay()->toDateString()]));

    expect($report->isCritical())->toBeTrue()
        ->and($source->fresh()->end_date)->toBeNull()->and($seat->fresh()->end_date)->toBeNull();
});

test('user duty service refuses to remove a duty held only through derived seats', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $this->actingAs(makeAdminUser($this->tenant));
    try {
        UserDutyService::syncDutiesForUser(collect(), collect([$this->targetDuty->id]),
            $this->holder, app(ModelAuthorizer::class), 'users.update.padalinys');
        $this->fail('Derived-only removal must be refused.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('current_duties');
    }
    expect($seat->fresh()->end_date)->toBeNull();
});

test('user duty service removal ends root rows while keeping derived rows', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $root = Dutiable::factory()->forDuty($this->targetDuty)->forUser($this->holder)->create(['start_date' => '2026-01-01', 'end_date' => null]);
    $this->actingAs(makeAdminUser($this->tenant));
    UserDutyService::syncDutiesForUser(collect(), collect([$this->targetDuty->id]),
        $this->holder, app(ModelAuthorizer::class), 'users.update.padalinys');

    expect($root->fresh()->end_date->toDateString())->toBe('2026-10-29')->and($seat->fresh()->end_date)->toBeNull();
});

test('user duty service adding a manual term does not reuse a derived row', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $this->actingAs(makeAdminUser($this->tenant));
    UserDutyService::syncDutiesForUser(collect([$this->targetDuty->id]), collect(),
        $this->holder, app(ModelAuthorizer::class), 'users.update.padalinys');

    expect($this->targetDuty->dutiables()->whereNull('via_dutiable_id')->count())->toBe(1)
        ->and($seat->fresh()->via_dutiable_id)->toBe($source->id);
});

test('batch adding a manual term leaves the derived row unchanged', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $before = $seat->fresh()->getAttributes();
    asUser(makeAdminUser($this->tenant))->post(route('duties.batchUpdateUsers', $this->targetDuty), [
        'user_changes' => [['user_id' => $this->holder->id, 'action' => 'add', 'start_date' => '2026-09-01']],
    ])->assertSessionHasNoErrors();

    expect($seat->fresh()->getAttributes())->toBe($before)
        ->and($this->targetDuty->dutiables()->whereNull('via_dutiable_id')->count())->toBe(1);
});

test('a future source seat is created immediately and grants early access', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource(['start_date' => today()->addMonth()->toDateString()]);
    expect($source->derivedDutiables()->sole()->start_date->toDateString())->toBe('2026-11-30')
        ->and($this->holder->fresh()->authorization_duties->contains('id', $this->targetDuty->id))->toBeTrue();
});

test('merge keeps nonoverlapping past terms and seats from both duties', function (): void {
    $duplicate = Duty::factory()->for($this->institution)->create();
    foreach ([$duplicate, $this->sourceDuty] as $duty) {
        $duty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    }
    $first = reconcilerSource(['end_date' => '2026-03-31']);
    $second = Dutiable::factory()->forDuty($duplicate)->forUser($this->holder)->create(['start_date' => '2026-05-01', 'end_date' => '2026-06-30']);
    ReconcileExOfficioSeats::forSource($first, includeHistory: true);
    ReconcileExOfficioSeats::forSource($second, includeHistory: true);
    $seats = [$first->derivedDutiables()->sole(), $second->derivedDutiables()->sole()];
    $periods = array_map(fn ($seat) => $seat->only(['start_date', 'end_date']), $seats);
    MergeDuties::execute($this->sourceDuty, collect([$duplicate]));

    foreach ($seats as $index => $seat) {
        expect($seat->fresh()->only(['start_date', 'end_date']))->toEqual($periods[$index]);
    }
});

test('duty reconciliation leaves drifted seats from ended sources untouched', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource(['end_date' => '2026-06-30']);
    $seat = Dutiable::factory()->forDuty($this->targetDuty)->forUser($this->holder)->create([
        'via_dutiable_id' => $source->id, 'start_date' => '2026-02-01', 'end_date' => '2026-05-31',
    ]);
    $before = $seat->fresh()->getAttributes();

    ReconcileExOfficioSeats::forDuty($this->sourceDuty);
    ReconcileExOfficioSeats::forDuty($this->targetDuty);

    expect($seat->fresh()->getAttributes())->toBe($before);
});

test('saving a duty form with unchanged links tenants and institution leaves drifted seats untouched', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $seat->update(['start_date' => '2026-02-01']);
    $before = $seat->fresh()->getAttributes();

    app(UpdateDuty::class)->execute($this->sourceDuty, [
        'name' => ['lt' => 'Atnaujintas pavadinimas', 'en' => 'Updated name'],
        'institution_id' => $this->institution->id, 'ex_officio_target_duty_ids' => [$this->targetDuty->id],
    ]);
    app(UpdateDuty::class)->execute($this->targetDuty, ['institution_id' => $this->institution->id]);

    expect($seat->fresh()->getAttributes())->toBe($before);
});

test('unchanged link selection does not reconcile a drifted seat', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $seat->update(['start_date' => '2026-02-01']);
    $before = $seat->fresh()->getAttributes();

    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);

    expect($seat->fresh()->getAttributes())->toBe($before);
});

test('reconciling a trashed source duty never deletes its future seats', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource(['start_date' => '2026-12-01']);
    $seat = $source->derivedDutiables()->sole();
    $before = $seat->fresh()->getAttributes();
    $this->sourceDuty->deleteQuietly();

    ReconcileExOfficioSeats::forSource($source);

    expect($seat->fresh()->getAttributes())->toBe($before);
});

test('reordering assignable tenants and changing quotas does not reconcile drifted seats', function (): void {
    $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $this->targetDuty->assignableTenants()->attach([$this->tenant->id, $other->id]);
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $seat->update(['start_date' => '2026-02-01']);
    $before = $seat->fresh()->getAttributes();

    app(UpdateDuty::class)->execute($this->targetDuty, [
        'institution_id' => $this->institution->id,
        'assignable_tenants' => [['tenant_id' => $other->id, 'quota' => 3], ['tenant_id' => $this->tenant->id, 'quota' => 2]],
    ]);

    expect($seat->fresh()->getAttributes())->toBe($before);
});

test('removing a rule ends started seats today and deletes upcoming seats through the model', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $future = reconcilerSource(['start_date' => '2026-12-01']);
    $futureSeat = $future->derivedDutiables()->sole();
    $closedSource = reconcilerSource();
    $closed = $closedSource->derivedDutiables()->sole();
    $closed->forceFill(['end_date' => '2026-09-30', 'ended_by_institution_deactivation' => true])->save();
    $snapshot = $closed->fresh()->getAttributes();
    $endedSource = reconcilerSource();
    $ended = $endedSource->derivedDutiables()->sole();
    $ended->update(['end_date' => '2026-09-30']);
    $endedSnapshot = $ended->fresh()->getAttributes();
    Event::fake([DutiableChanged::class]);

    SyncExOfficioTargets::execute($this->sourceDuty, []);

    expect($seat->fresh()->end_date->toDateString())->toBe('2026-10-30')
        ->and($closed->fresh()->getAttributes())->toBe($snapshot)
        ->and($ended->fresh()->getAttributes())->toBe($endedSnapshot)
        ->and($this->sourceDuty->exOfficioTargetDuties()->count())->toBe(0);
    $this->assertModelMissing($futureSeat);
    Event::assertDispatched(DutiableChanged::class,
        fn ($event) => $event->dutiableRowId === $seat->id);
    Event::assertDispatched(DutiableChanged::class,
        fn ($event) => $event->dutiableRowId === $futureSeat->id);
});

test('editing a term after rule removal leaves its attached history alone', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    SyncExOfficioTargets::execute($this->sourceDuty, []);
    $before = $seat->fresh()->getAttributes();
    $this->travelTo('2026-11-03');

    $source->update(['start_date' => '2026-02-01', 'end_date' => '2027-01-31']);

    expect($seat->fresh()->getAttributes())->toBe($before)
        ->and($source->derivedDutiables()->count())->toBe(1);
});

test('readding a rule mirrors the term into its existing seat again', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    SyncExOfficioTargets::execute($this->sourceDuty, []);
    $this->travelTo('2026-11-03');

    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);

    expect($source->derivedDutiables()->sole()->id)->toBe($seat->id)
        ->and($seat->fresh()->start_date->toDateString())->toBe('2026-01-01')
        ->and($seat->fresh()->end_date)->toBeNull();
});

test('adding rules mirrors current and upcoming terms but leaves ended history for review', function (): void {
    $current = reconcilerSource();
    $future = reconcilerSource(['start_date' => '2026-12-01', 'end_date' => '2027-06-30']);
    $past = reconcilerSource(['end_date' => '2026-06-30']);

    SyncExOfficioTargets::execute($this->sourceDuty, [$this->sourceDuty->id, $this->targetDuty->id, $this->targetDuty->id]);

    expect($this->sourceDuty->exOfficioTargetDuties()->count())->toBe(1)
        ->and($current->derivedDutiables()->sole()->start_date->toDateString())->toBe('2026-01-01')
        ->and($future->derivedDutiables()->sole()->start_date->toDateString())->toBe('2026-12-01')
        ->and($future->derivedDutiables()->sole()->end_date->toDateString())->toBe('2027-06-30')
        ->and($past->derivedDutiables()->count())->toBe(0);
});

test('merged terms keep one seat per target and copy its independent details', function (): void {
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    $survivor = reconcilerSource();
    $loser = reconcilerSource(['start_date' => '2026-02-01']);
    $keptSeat = $survivor->derivedDutiables()->sole();
    $removedSeat = $loser->derivedDutiables()->sole();
    $removedSeat->update(['additional_email' => 'seat@example.com', 'description' => ['lt' => 'Pastaba', 'en' => 'Note']]);

    CollapseOverlappingDutiables::execute($this->sourceDuty);

    expect($survivor->derivedDutiables()->sole()->id)->toBe($keptSeat->id)
        ->and($keptSeat->fresh()->additional_email)->toBe('seat@example.com')
        ->and($keptSeat->fresh()->getTranslation('description', 'en'))->toBe('Note');
    $this->assertModelMissing($loser);
    $this->assertModelMissing($removedSeat);
});

test('merging duties deduplicates rules in both directions and removes self links', function (): void {
    $duplicate = Duty::factory()->for($this->institution)->create();
    $other = Duty::factory()->for($this->institution)->create();
    $source = reconcilerSource();
    $this->sourceDuty->exOfficioTargetDuties()->attach([$this->targetDuty->id, $duplicate->id]);
    $duplicate->exOfficioTargetDuties()->attach([$this->targetDuty->id, $this->sourceDuty->id]);
    $other->exOfficioTargetDuties()->attach([$this->sourceDuty->id, $duplicate->id]);

    MergeDuties::execute($this->sourceDuty, collect([$duplicate]));

    expect($this->sourceDuty->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$this->targetDuty->id])
        ->and($other->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$this->sourceDuty->id])
        ->and($source->derivedDutiables()->sole()->start_date->toDateString())->toBe('2026-01-01');
});

test('review creates only selected ended terms and leaves the others unticked', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $first = reconcilerSource(['end_date' => '2026-02-28']);
    $second = reconcilerSource(['start_date' => '2026-04-01', 'end_date' => '2026-05-31']);
    $actor = makeAdminUser($this->tenant);
    $plan = PlanExOfficioReview::forDuty($this->sourceDuty, $actor);
    $item = collect($plan['items'])->firstWhere('source_id', $first->id);
    expect($item)->toMatchArray(['historical' => true, 'selected' => false]);

    ApplyExOfficioReview::execute($this->sourceDuty, $actor, [
        'fingerprint' => $plan['fingerprint'], 'item_ids' => [$item['id']],
    ]);

    expect($first->derivedDutiables()->sole()->end_date->toDateString())->toBe('2026-02-28')
        ->and($second->derivedDutiables()->count())->toBe(0);
});

test('repeated syncing after reopening preserves the resumed start and closed history', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);
    $source = reconcilerSource();
    $closed = $source->derivedDutiables()->sole();
    $closed->forceFill(['end_date' => '2026-09-30', 'ended_by_institution_deactivation' => true])->save();
    $snapshot = $closed->fresh()->getAttributes();
    ReconcileExOfficioSeats::forSource($source);
    $resumed = $source->derivedDutiables()->where('ended_by_institution_deactivation', false)->sole();
    $this->travelTo('2026-11-05');

    $source->update(['start_date' => '2025-01-01', 'end_date' => '2027-06-30']);

    expect($closed->fresh()->getAttributes())->toBe($snapshot)
        ->and($resumed->fresh()->start_date->toDateString())->toBe('2026-10-30')
        ->and($resumed->fresh()->end_date->toDateString())->toBe('2027-06-30');
});

test('reconciliation deletes exact-detail duplicates but leaves distinct details to report', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);
    $source = reconcilerSource();
    $seat = $source->derivedDutiables()->sole();
    $seat->update(['additional_email' => 'kept@example.com']);
    $exact = $seat->replicate();
    $exact->save();
    $distinct = $seat->replicate();
    $distinct->additional_email = 'other@example.com';
    $distinct->save();

    $findings = ReconcileExOfficioSeats::forSource($source);

    $this->assertModelExists($seat);
    $this->assertModelMissing($exact);
    $this->assertModelExists($distinct);
    expect($findings)->toBe([['kind' => 'duplicate', 'row_id' => $distinct->id]]);
});

test('rule changes audit translated duty labels and dispatch relation changes once', function (): void {
    $this->targetDuty->update(['name' => ['lt' => 'Tikslinės pareigos', 'en' => 'Target duty']]);
    Event::fake([SearchRelationChanged::class]);

    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);
    SyncExOfficioTargets::execute($this->sourceDuty, [$this->targetDuty->id]);

    $activity = Activity::forSubject($this->sourceDuty)->where('event', 'relation_updated')->sole();
    expect($activity->properties->get('relation'))->toBe('exOfficioTargetDuties')
        ->and($activity->properties->get('attached'))->toBe([['id' => $this->targetDuty->id, 'label' => 'Tikslinės pareigos']]);
    Event::assertDispatchedTimes(SearchRelationChanged::class, 1);
});
