<?php

use App\Actions\Duties\BuildDutyLineage;
use App\Actions\MergeDuties;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create([
        'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
    ]);
});

function updateDutyPayload(Duty $duty, array $overrides = []): array
{
    return [
        'name' => ['lt' => $duty->getTranslation('name', 'lt'), 'en' => $duty->getTranslation('name', 'en') ?? ''],
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        ...$overrides,
    ];
}

function holdDuty(Duty $duty, User $user, string $start, ?string $end = null): Dutiable
{
    return Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

describe('active flag', function (): void {
    test('an empty duty can be marked inactive', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, ['is_active' => false]))
            ->assertSessionHasNoErrors();

        expect($this->duty->fresh()->is_active)->toBeFalse();
    });

    test('a duty someone still holds cannot be marked inactive', function (): void {
        holdDuty($this->duty, User::factory()->create(), now()->subMonth()->toDateString());

        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, ['is_active' => false]))
            ->assertSessionHasErrors('is_active');

        expect($this->duty->fresh()->is_active)->toBeTrue();
    });

    test('the member wizard no longer offers an inactive duty', function (): void {
        $this->duty->update(['is_active' => false]);
        $active = Duty::factory()->for($this->institution)->create();

        asUserWithInertia($this->admin)->get(route('duties.updateUsersWizard'))
            ->assertInertia(fn (Assert $page) => $page->where('institutions', function ($institutions) use ($active): bool {
                $dutyIds = collect($institutions)->flatMap(fn ($institution) => collect($institution['duties'] ?? [])->pluck('id'));

                return $dutyIds->contains($active->id) && ! $dutyIds->contains($this->duty->id);
            }));
    });

    test('an inactive duty is not reported as vacant', function (): void {
        $this->duty->update(['is_active' => false]);

        $response = asUser($this->admin)->getJson(route('api.v1.admin.duties.index', ['filters' => json_encode(['data_quality' => 'vacant'])]))->assertOk();

        expect(collect($response->json('data.items'))->pluck('id'))->not->toContain($this->duty->id);
    });
});

describe('former names', function (): void {
    test('are saved with the duty', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, [
            'name' => ['lt' => 'Ryšių su visuomene koordinatorius', 'en' => 'PR Coordinator'],
            'former_names' => [['name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'], 'used_until' => '2026-06-30']],
        ]))->assertSessionHasNoErrors();

        $former = $this->duty->formerNames()->sole();

        expect($former->getTranslation('name', 'lt'))->toBe('Komunikacijos koordinatorius')
            ->and($former->used_until->toDateString())->toBe('2026-06-30');
    });

    test('cannot be dated in the future', function (): void {
        asUser($this->admin)->patch(route('duties.update', $this->duty), updateDutyPayload($this->duty, [
            'former_names' => [['name' => ['lt' => 'Senas'], 'used_until' => now()->addYear()->toDateString()]],
        ]))->assertSessionHasErrors('former_names.0.used_until');
    });

    test('a term reads with the name in force when it began', function (): void {
        DutyName::factory()->for($this->duty)->create(['name' => ['lt' => 'Viešųjų ryšių koordinatorius'], 'used_until' => '2021-06-30']);
        DutyName::factory()->for($this->duty)->create(['name' => ['lt' => 'Informacijos koordinatorius'], 'used_until' => '2018-06-30']);

        app()->setLocale('lt');

        expect($this->duty->nameOn('2017-09-01'))->toBe('Informacijos koordinatorius')
            ->and($this->duty->nameOn('2019-09-01'))->toBe('Viešųjų ryšių koordinatorius')
            ->and($this->duty->nameOn('2024-09-01'))->toBe('Komunikacijos koordinatorius');
    });
});

function linkPredecessor(Duty $successor, Duty $predecessor, string $effectiveOn): void
{
    $successor->predecessors()->attach($predecessor, ['effective_on' => $effectiveOn]);
}

function lineageTermNames(Duty $duty): array
{
    return collect(BuildDutyLineage::execute($duty)['predecessorTerms'])->pluck('name')->sort()->values()->all();
}

describe('predecessors', function (): void {
    beforeEach(function (): void {
        $this->combined = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Socialinis-akademinis koordinatorius', 'en' => 'Social-academic coordinator'],
            'is_active' => false,
        ]);
    });

    test('the duty record lists a linked predecessor and its holders', function (): void {
        holdDuty($this->combined, User::factory()->create(['name' => 'Senas Narys']), '2018-07-01', '2019-06-30');

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id, 'effective_on' => '2019-07-01'])
            ->assertSessionHasNoErrors();

        asUserWithInertia($this->admin)->get(route('duties.show', $this->duty))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('lineage')
                ->loadDeferredProps('dutyPanels', fn (Assert $reload) => $reload
                    ->where('lineage.events.0.kind', 'took_over')
                    ->where('lineage.events.0.date', '2019-07-01')
                    ->where('lineage.events.0.duties.0.id', $this->combined->id)
                    ->where('lineage.predecessorTerms.0.name', 'Senas Narys')
                    ->where('lineage.predecessorTerms.0.duty_name', 'Socialinis-akademinis koordinatorius')));
    });

    test('a link needs the day the successor took over', function (): void {
        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id])
            ->assertSessionHasErrors('effective_on');
    });

    test('a duty another tenant owns cannot be linked', function (): void {
        $foreign = Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first()))->create();

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $foreign->id, 'effective_on' => '2020-01-01'])
            ->assertSessionHasErrors('predecessor_id');

        expect($this->duty->predecessors()->exists())->toBeFalse();
    });

    test('a duty cannot precede itself, nor its successor on the same day', function (): void {
        linkPredecessor($this->combined, $this->duty, '2020-09-01');

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->duty->id, 'effective_on' => '2020-09-01'])
            ->assertSessionHasErrors('predecessor_id');
        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id, 'effective_on' => '2020-09-01'])
            ->assertSessionHasErrors('predecessor_id');
    });

    test('a duty can take its work back from its successor on a later day', function (): void {
        linkPredecessor($this->combined, $this->duty, '2020-09-01');

        asUser($this->admin)->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id, 'effective_on' => '2023-09-01'])
            ->assertSessionHasNoErrors();

        expect($this->duty->predecessors()->wherePivot('effective_on', '2023-09-01')->exists())->toBeTrue();
    });

    test('a merged duty shows each predecessor\'s terms from before the merge only', function (): void {
        $academic = Duty::factory()->for($this->institution)->create();
        holdDuty($this->duty, User::factory()->create(['name' => 'Socialinis A']), '2018-07-01', '2019-06-30');
        holdDuty($academic, User::factory()->create(['name' => 'Akademinis A']), '2018-07-01', '2019-06-30');
        holdDuty($academic, User::factory()->create(['name' => 'Akademinis Vėliau']), '2021-07-01');
        linkPredecessor($this->combined, $this->duty, '2019-07-01');
        linkPredecessor($this->combined, $academic, '2019-07-01');

        expect(lineageTermNames($this->combined))->toBe(['Akademinis A', 'Socialinis A']);
    });

    test('a duty split back into its earlier parts gets the merged terms, not its partner\'s', function (): void {
        $academic = Duty::factory()->for($this->institution)->create();
        holdDuty($this->duty, User::factory()->create(['name' => 'Socialinis Pirmas']), '2017-07-01', '2019-06-30');
        holdDuty($academic, User::factory()->create(['name' => 'Akademinis Pirmas']), '2017-07-01', '2019-06-30');
        holdDuty($this->combined, User::factory()->create(['name' => 'Bendras']), '2019-07-01', '2022-06-30');
        holdDuty($this->duty, User::factory()->create(['name' => 'Socialinis Antras']), '2022-07-01');
        linkPredecessor($this->combined, $this->duty, '2019-07-01');
        linkPredecessor($this->combined, $academic, '2019-07-01');
        linkPredecessor($this->duty, $this->combined, '2022-07-01');
        linkPredecessor($academic, $this->combined, '2022-07-01');

        expect(lineageTermNames($this->duty))->toBe(['Bendras']);
    });

    test('a split duty follows the chain back through its predecessor', function (): void {
        $original = Duty::factory()->for($this->institution)->create();
        holdDuty($original, User::factory()->create(['name' => 'Pirmtakės Pirmtakė']), '2015-07-01', '2017-06-30');
        holdDuty($this->combined, User::factory()->create(['name' => 'Bendras']), '2017-07-01', '2020-06-30');
        linkPredecessor($this->combined, $original, '2017-07-01');
        linkPredecessor($this->duty, $this->combined, '2020-07-01');

        expect(lineageTermNames($this->duty))->toBe(['Bendras', 'Pirmtakės Pirmtakė']);
    });

    test('linking needs the right to edit the duty', function (): void {
        asUser(makeUser($this->tenant))->post(route('duties.predecessors.store', $this->duty), ['predecessor_id' => $this->combined->id, 'effective_on' => '2020-01-01'])
            ->assertForbidden();
    });

    test('unlinking only reaches this duty\'s own links', function (): void {
        $other = Duty::factory()->for($this->institution)->create();
        linkPredecessor($other, $this->combined, '2020-09-01');
        $linkId = $other->predecessors()->first()->pivot->id;

        asUser($this->admin)->delete(route('duties.predecessors.destroy', [$this->duty, $linkId]))->assertNotFound();

        expect($other->predecessors()->exists())->toBeTrue();
    });

    test('merging duties carries former names and dated links over to the kept duty', function (): void {
        $twin = Duty::factory()->for($this->institution)->create();
        DutyName::factory()->for($twin)->create();
        linkPredecessor($twin, $this->combined, '2020-09-01');
        linkPredecessor($this->duty, $this->combined, '2020-09-01');

        MergeDuties::execute($this->duty, collect([$twin]));

        expect($this->duty->formerNames()->count())->toBe(1)
            ->and($this->duty->predecessors()->pluck('duties.id')->all())->toBe([$this->combined->id])
            ->and($this->duty->predecessors()->first()->pivot->effective_on->toDateString())->toBe('2020-09-01')
            ->and(DB::table('duty_successions')->where('successor_id', $twin->id)->exists())->toBeFalse();
    });
});

describe('folding a link into a rename', function (): void {
    beforeEach(function (): void {
        $this->oldName = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Informacijos koordinatorius', 'en' => 'Information Coordinator'],
            'is_active' => false,
        ]);
        $this->term = holdDuty($this->oldName, User::factory()->create(), '2018-07-01', '2020-06-30');
        linkPredecessor($this->duty, $this->oldName, '2020-07-01');
        $this->linkId = $this->duty->predecessors()->first()->pivot->id;
    });

    test('moves the terms onto the duty and keeps the old name until the day before', function (): void {
        asUser($this->admin)->post(route('duties.predecessors.foldRename', [$this->duty, $this->linkId]))
            ->assertSessionHasNoErrors();

        $former = $this->duty->formerNames()->sole();

        expect(Duty::query()->find($this->oldName->id))->toBeNull()
            ->and($this->term->fresh()->duty_id)->toBe($this->duty->id)
            ->and($former->getTranslation('name', 'lt'))->toBe('Informacijos koordinatorius')
            ->and($former->used_until->toDateString())->toBe('2020-06-30')
            ->and($this->duty->predecessors()->exists())->toBeFalse();
    });

    test('keeps the old duty\'s change history on the duty', function (): void {
        activity()->performedOn($this->oldName)->event('updated')->log('updated');

        asUser($this->admin)->post(route('duties.predecessors.foldRename', [$this->duty, $this->linkId]));

        expect(Activity::forSubject($this->duty)->where('event', 'updated')->exists())->toBeTrue()
            ->and(Activity::query()->where('subject_type', 'duty')->where('subject_id', $this->oldName->id)->pluck('event')->all())->toBe(['deleted']);
    });

    test('is offered on a one-to-one link', function (): void {
        asUserWithInertia($this->admin)->get(route('duties.show', $this->duty))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps('dutyPanels', fn (Assert $reload) => $reload
                ->where('lineage.events.0.duties.0.can_fold', true)));
    });

    test('is refused when the predecessor also went to another duty that day', function (): void {
        linkPredecessor(Duty::factory()->for($this->institution)->create(), $this->oldName, '2020-07-01');

        asUser($this->admin)->post(route('duties.predecessors.foldRename', [$this->duty, $this->linkId]))
            ->assertSessionHasErrors('succession');

        expect(Duty::query()->find($this->oldName->id))->not->toBeNull();
    });

    test('is refused across institutions', function (): void {
        $this->oldName->update(['institution_id' => Institution::factory()->for($this->tenant)->create()->id]);

        asUser($this->admin)->post(route('duties.predecessors.foldRename', [$this->duty, $this->linkId]))
            ->assertSessionHasErrors('succession');
    });

    test('needs the right to edit the duty', function (): void {
        asUser(makeUser($this->tenant))->post(route('duties.predecessors.foldRename', [$this->duty, $this->linkId]))
            ->assertForbidden();

        expect(Duty::query()->find($this->oldName->id))->not->toBeNull();
    });
});

describe('history filter', function (): void {
    test('lists renamed, linked or either', function (): void {
        $renamed = $this->duty;
        DutyName::factory()->for($renamed)->create();
        $linkedFrom = Duty::factory()->for($this->institution)->create();
        $linkedTo = Duty::factory()->for($this->institution)->create();
        linkPredecessor($linkedTo, $linkedFrom, '2020-07-01');
        $plain = Duty::factory()->for($this->institution)->create();

        $ids = fn (string $history) => collect(asUser($this->admin)
            ->getJson(route('api.v1.admin.duties.index', ['filters' => json_encode(['history' => $history]), 'per_page' => 100]))
            ->assertOk()->json('data.items'))->pluck('id');

        expect($ids('renamed'))->toContain($renamed->id)->not->toContain($linkedFrom->id)
            ->and($ids('linked'))->toContain($linkedFrom->id, $linkedTo->id)->not->toContain($renamed->id)
            ->and($ids('any'))->toContain($renamed->id, $linkedFrom->id, $linkedTo->id)->not->toContain($plain->id);
    });
});
