<?php

use App\Actions\Dutiables\PlanExOfficioReview;
use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\GetRecentAccessChanges;
use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccessChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
    Event::fake([DutiableChanged::class]);
    $this->tenant = Tenant::query()->first();
    $role = Role::findByName('Komunikacijos koordinatorius');
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys', 'users.read.padalinys']);
    $this->manager = makeUser($this->tenant);
    $this->manager->duties()->first()->assignRole($role);
    $institution = Institution::factory()->for($this->tenant)->create();
    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);
    $this->holder = User::factory()->create();
    $this->source = reviewFixture([
        'duty_id' => $this->sourceDuty->id, 'dutiable_id' => $this->holder->id,
        'start_date' => '2024-01-01', 'end_date' => '2024-12-31',
    ]);
});

function reviewFixture(array $attributes): Dutiable
{
    return ReconcileExOfficioSeats::paused(fn () => Dutiable::factory()->create($attributes));
}

function reviewSourceUpdate(Dutiable $source, array $attributes): void
{
    ReconcileExOfficioSeats::paused(fn () => $source->update($attributes));
}

function reviewPreview(): array
{
    return asUser(test()->manager)->getJson(route('duties.exOfficioReview.preview', test()->targetDuty))->assertOk()->json('data');
}

function reviewApply(array $plan, ?array $ids = null, array $extra = []): TestResponse
{
    return asUser(test()->manager)->post(route('duties.exOfficioReview.apply', test()->targetDuty), [
        'fingerprint' => $plan['fingerprint'], 'item_ids' => $ids ?? array_column($plan['items'], 'id'), ...$extra,
    ]);
}

function reviewCandidate(array $extra = []): Dutiable
{
    return reviewFixture([
        'duty_id' => test()->targetDuty->id, 'dutiable_id' => test()->holder->id,
        'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'assignment_origin' => 'unknown', ...$extra,
    ]);
}

test('links matching history with the resolved tenant and preserves details', function (): void {
    $this->targetDuty->assignableTenants()->attach($this->tenant);
    $row = reviewCandidate(['additional_email' => 'seat@example.com']);
    $plan = reviewPreview();
    expect($plan['items'][0]['kind'])->toBe('link')
        ->and($plan['items'][0]['selected'])->toBeFalse();
    reviewApply($plan)->assertSessionHasNoErrors();
    expect($row->fresh()->via_dutiable_id)->toBe($this->source->id)
        ->and($row->fresh()->tenant_id)->toBe($this->tenant->id)
        ->and($row->fresh()->additional_email)->toBe('seat@example.com');
    $this->source->save();
    expect(Dutiable::where('via_dutiable_id', $this->source->id)->count())->toBe(1);
});

test('splits inclusive remainders and leaves the original photo on its row', function (): void {
    $row = reviewCandidate(['start_date' => '2023-12-01', 'end_date' => '2025-02-01',
        'additional_email' => 'seat@example.com', 'additional_photo' => '/photo.jpg',
        'description' => ['lt' => '<p>Tekstas</p>', 'en' => '<p>Text</p>']]);
    reviewApply(reviewPreview())->assertSessionHasNoErrors();
    $remainders = Dutiable::where('duty_id', $this->targetDuty->id)->where('assignment_origin', 'manual')->orderBy('start_date')->get();
    expect($remainders)->toHaveCount(2)
        ->and($remainders[0]->end_date->toDateString())->toBe('2023-12-31')
        ->and($remainders[1]->start_date->toDateString())->toBe('2025-01-01')
        ->and($remainders[1]->additional_email)->toBe('seat@example.com')
        ->and($remainders[1]->getTranslation('description', 'en'))->toBe('<p>Text</p>')
        ->and($remainders[1]->additional_photo)->toBeNull()
        ->and($row->fresh()->additional_photo)->toBe('/photo.jpg');
});

test('seven-day overlaps and unmatched or manual rows are never written', function (): void {
    $noise = reviewCandidate(['start_date' => '2024-12-25', 'end_date' => '2025-12-31']);
    $unmatched = reviewCandidate(['start_date' => '2020-01-01', 'end_date' => '2020-12-31']);
    $manual = reviewCandidate(['assignment_origin' => 'manual']);
    $before = collect([$noise, $unmatched, $manual])->map(fn ($row) => $row->fresh()->getAttributes())->all();
    $plan = reviewPreview();
    expect(array_column($plan['items'], 'kind'))->toBe(['create']);
    reviewApply($plan)->assertSessionHasNoErrors();
    expect(collect([$noise, $unmatched, $manual])->map(fn ($row) => $row->fresh()->getAttributes())->all())->toBe($before);
});

test('an orphan defaults to ending with its source', function (): void {
    $row = reviewCandidate(['end_date' => null]);
    $plan = reviewPreview();
    expect($plan['items'][0]['orphan'])->toBeTrue()->and($plan['items'][0]['selected'])->toBeFalse();
    reviewApply($plan)->assertSessionHasNoErrors();
    expect($row->fresh()->end_date->toDateString())->toBe('2024-12-31')
        ->and(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(1);
});

test('an orphan can explicitly keep its open-ended independent remainder', function (): void {
    reviewCandidate(['end_date' => null]);
    $plan = reviewPreview();
    reviewApply($plan, extra: ['remainder_choices' => [$plan['items'][0]['id'] => 'keep']])->assertSessionHasNoErrors();
    $remainder = Dutiable::where('duty_id', $this->targetDuty->id)->where('assignment_origin', 'manual')->sole();
    expect($remainder->start_date->toDateString())->toBe('2025-01-01')->and($remainder->end_date)->toBeNull();
});

test('backfills ended sources and remains idempotent on a real source save', function (): void {
    $plan = reviewPreview();
    expect($plan['items'][0]['kind'])->toBe('create')->and($plan['items'][0]['selected'])->toBeFalse();
    reviewApply($plan)->assertSessionHasNoErrors();
    $this->source->save();
    expect(Dutiable::where('via_dutiable_id', $this->source->id)->where('duty_id', $this->targetDuty->id)->count())->toBe(1)
        ->and(reviewPreview()['items'])->toBeEmpty();
});

test('resyncs linked dates without changing independent manual rows', function (): void {
    $row = reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio', 'end_date' => '2025-01-31']);
    $manual = reviewCandidate(['assignment_origin' => 'manual']);
    $plan = reviewPreview();
    expect($plan['items'][0]['kind'])->toBe('resync');
    reviewApply($plan)->assertSessionHasNoErrors();
    expect($row->fresh()->end_date->toDateString())->toBe('2024-12-31')
        ->and($manual->fresh()->assignment_origin)->toBe('manual');
});

test('proposes duplicate deletion and transfers bilingual details without widening linked dates', function (): void {
    $linked = reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio', 'additional_email' => null,
        'description' => ['lt' => '<p>Palikti</p>', 'en' => '']]);
    $duplicate = reviewCandidate(['additional_email' => 'duplicate@example.com',
        'description' => ['lt' => '<p>Kitas</p>', 'en' => '<p>Keep English</p>']]);
    $plan = reviewPreview();
    expect($plan['items'][0]['kind'])->toBe('remove_duplicate')->and($plan['items'][0]['selected'])->toBeFalse();
    reviewApply($plan)->assertSessionHasNoErrors();
    $this->assertModelMissing($duplicate);
    expect($linked->fresh()->additional_email)->toBe('duplicate@example.com')
        ->and($linked->fresh()->end_date->toDateString())->toBe('2024-12-31')
        ->and($linked->fresh()->getTranslation('description', 'lt'))->toBe('<p>Palikti</p>')
        ->and($linked->fresh()->getTranslation('description', 'en'))->toBe('<p>Keep English</p>');
});

test('a candidate spanning several sources links to the largest overlap and creates the other seat', function (): void {
    reviewSourceUpdate($this->source, ['end_date' => '2024-08-31']);
    $second = reviewFixture([
        'duty_id' => $this->sourceDuty->id, 'dutiable_id' => $this->holder->id,
        'start_date' => '2024-09-01', 'end_date' => '2024-12-31',
    ]);
    $row = reviewCandidate();
    $plan = reviewPreview();
    expect(array_column($plan['items'], 'kind'))->toContain('link', 'create');
    reviewApply($plan)->assertSessionHasNoErrors();
    expect($row->fresh()->via_dutiable_id)->toBe($this->source->id)
        ->and(Dutiable::where('via_dutiable_id', $second->id)->where('duty_id', $this->targetDuty->id)->count())->toBe(1)
        ->and(Dutiable::where('duty_id', $this->targetDuty->id)->where('assignment_origin', 'manual')->count())->toBe(0);
});

test('changed source dates invalidate a preview with 409 and no partial changes', function (): void {
    $row = reviewCandidate();
    $plan = reviewPreview();
    reviewSourceUpdate($this->source, ['end_date' => '2024-11-30']);
    reviewApply($plan)->assertConflict();
    expect($row->fresh()->via_dutiable_id)->toBeNull();
});

test('rejects a crafted item id even when the caller can manage the other duty', function (): void {
    $row = reviewCandidate();
    $other = Duty::factory()->create(['institution_id' => $this->targetDuty->institution_id]);
    $this->sourceDuty->exOfficioTargetDuties()->attach($other);
    $foreign = PlanExOfficioReview::execute([$other->id], $this->manager)['items'][0]['id'];
    $plan = reviewPreview();
    reviewApply($plan, [$plan['items'][0]['id'], $foreign])->assertUnprocessable();
    expect($row->fresh()->via_dutiable_id)->toBeNull();
});

test('entry requires permission and cannot cross tenants', function (): void {
    $stranger = makeUser($this->tenant);
    asUser($stranger)->getJson(route('duties.exOfficioReview.preview', $this->targetDuty))->assertForbidden();
    asUser($stranger)->postJson(route('duties.exOfficioReview.apply', $this->targetDuty), [])->assertForbidden();
    $foreign = Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail()))->create();
    asUser($this->manager)->getJson(route('duties.exOfficioReview.preview', $foreign))->assertForbidden();
    asUser($this->manager)->postJson(route('duties.exOfficioReview.apply', $foreign), [])->assertForbidden();
});

test('unmanageable sources are blocked and cannot be selected', function (): void {
    $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail())->create();
    $this->sourceDuty->update(['institution_id' => $foreign->id]);
    $row = reviewCandidate();
    $plan = reviewPreview();
    expect($plan['items'])->toBeEmpty()->and($plan['blocked'][0]['reason'])->toBe('permission');
    reviewApply($plan, ['link:'.$this->targetDuty->id.':'.$this->source->id.':'.$row->id])->assertUnprocessable();
    expect($row->fresh()->via_dutiable_id)->toBeNull();
});

test('inactive institutions and chained sources never produce writable proposals', function (): void {
    reviewSourceUpdate($this->source, ['via_dutiable_id' => $this->manager->dutiables()->first()->id]);
    expect(reviewPreview()['blocked'][0]['reason'])->toBe('chain');
    $this->sourceDuty->institution->update(['is_active' => false]);
    expect(reviewPreview()['items'])->toBeEmpty();
});

test('backfilled endings do not add access notices for the target duty', function (): void {
    Notification::fake();
    reviewSourceUpdate($this->source, ['end_date' => '2026-10-29']);
    reviewApply(reviewPreview())->assertSessionHasNoErrors();
    expect(collect(GetRecentAccessChanges::execute($this->holder, 0))->where('dutyName', (string) $this->targetDuty->name))->toBeEmpty();
    $this->artisan('notifications:access-changes')->assertSuccessful();
    Notification::assertSentTo($this->holder, AccessChangedNotification::class, fn (AccessChangedNotification $notice) => ! collect($notice->context($this->holder))->contains(fn (array $entry) => str_contains($entry['value'], (string) $this->targetDuty->name)));
});

test('split starts today are synthetic and do not send an access notice', function (): void {
    Notification::fake();
    reviewSourceUpdate($this->source, ['end_date' => '2026-10-29']);
    reviewCandidate(['end_date' => null]);
    $plan = reviewPreview();
    reviewApply($plan, extra: ['remainder_choices' => [$plan['items'][0]['id'] => 'keep']])->assertSessionHasNoErrors();
    $changes = GetRecentAccessChanges::execute($this->holder, 0);
    expect(collect($changes)->where('kind', 'started'))->toBeEmpty();
    $this->artisan('notifications:access-changes')->assertSuccessful();
    Notification::assertSentTo($this->holder, AccessChangedNotification::class, fn ($notice) => true);
});

test('ending the actor own seat warns and rolls back until acknowledged', function (): void {
    $ownDuty = $this->manager->duties()->first();
    $ownRow = Dutiable::findOrFail($ownDuty->pivot->id);
    $this->sourceDuty->exOfficioTargetDuties()->attach($ownDuty);
    $source = reviewFixture([
        'duty_id' => $this->sourceDuty->id, 'dutiable_id' => $this->manager->id,
        'start_date' => '2020-01-01', 'end_date' => '2020-12-31',
    ]);
    $ownRow->update(['assignment_origin' => 'unknown', 'start_date' => '2020-01-01', 'end_date' => null]);
    $plan = asUser($this->manager)->getJson(route('duties.exOfficioReview.preview', $ownDuty))->assertOk()->json('data');
    $item = collect($plan['items'])->firstWhere('source_id', $source->id);
    $payload = ['fingerprint' => $plan['fingerprint'], 'item_ids' => [$item['id']]];
    asUserWithInertia($this->manager)->post(route('duties.exOfficioReview.apply', $ownDuty), $payload)
        ->assertRedirect()->assertSessionHas('access_change_warning');
    expect($ownRow->fresh()->end_date)->toBeNull()->and($ownRow->fresh()->via_dutiable_id)->toBeNull();
    asUserWithInertia($this->manager)->post(route('duties.exOfficioReview.apply', $ownDuty), [...$payload, 'acknowledge_access_change' => true])
        ->assertRedirect(route('dashboard'));
    expect($ownRow->fresh()->end_date->toDateString())->toBe('2020-12-31');
});

test('rejects a partial multi-source link that would erase unselected history', function (): void {
    reviewSourceUpdate($this->source, ['end_date' => '2024-08-31']);
    reviewFixture([
        'duty_id' => $this->sourceDuty->id, 'dutiable_id' => $this->holder->id,
        'start_date' => '2024-09-01', 'end_date' => '2024-12-31',
    ]);
    $row = reviewCandidate();
    $plan = reviewPreview();
    $link = collect($plan['items'])->firstWhere('kind', 'link');
    reviewApply($plan, [$link['id']])->assertUnprocessable();
    expect($row->fresh()->via_dutiable_id)->toBeNull();
});

test('a resync cannot make a selected duplicate deletion lose historical coverage', function (): void {
    reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio', 'end_date' => '2025-12-31']);
    $duplicate = reviewCandidate(['end_date' => '2025-12-31']);
    $plan = reviewPreview();
    reviewApply($plan)->assertUnprocessable();
    $this->assertModelExists($duplicate);
});

test('a source duty entry reviews all of its targets', function (): void {
    $other = Duty::factory()->create(['institution_id' => $this->targetDuty->institution_id]);
    $this->sourceDuty->exOfficioTargetDuties()->attach($other);
    $plan = asUser($this->manager)->getJson(route('duties.exOfficioReview.preview', $this->sourceDuty))->assertOk()->json('data');
    expect(collect($plan['items'])->pluck('target_duty_id')->sort()->values()->all())
        ->toBe(collect([$this->targetDuty->id, $other->id])->sort()->values()->all());
});

test('source and target institutions must remain active between preview and apply', function (): void {
    $row = reviewCandidate();
    $plan = reviewPreview();
    $this->sourceDuty->institution->update(['is_active' => false]);
    reviewApply($plan)->assertConflict();
    expect($row->fresh()->via_dutiable_id)->toBeNull();
});

test('keeping a current orphan retains even a one-day trailing remainder', function (): void {
    reviewSourceUpdate($this->source, ['end_date' => '2026-10-29']);
    reviewCandidate(['end_date' => '2026-10-30']);
    $plan = reviewPreview();
    reviewApply($plan, extra: ['remainder_choices' => [$plan['items'][0]['id'] => 'keep']])->assertSessionHasNoErrors();
    $remainder = Dutiable::where('duty_id', $this->targetDuty->id)->where('assignment_origin', 'manual')->sole();
    expect($remainder->start_date->toDateString())->toBe('2026-10-30')
        ->and($remainder->end_date->toDateString())->toBe('2026-10-30');
});

test('institution-closed derived history is never resynced or recreated', function (): void {
    $row = reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio']);
    $row->forceFill(['ended_by_institution_deactivation' => true])->save();
    expect(reviewPreview()['items'])->toBeEmpty()
        ->and($row->fresh()->ended_by_institution_deactivation)->toBeTrue();
});

test('requires authentication for preview and apply', function (): void {
    $this->get(route('duties.exOfficioReview.preview', $this->targetDuty))->assertRedirect();
    $this->post(route('duties.exOfficioReview.apply', $this->targetDuty), [])->assertRedirect();
});

test('eight inclusive days match and an ended candidate reopening current access is not preselected', function (): void {
    $row = reviewCandidate(['start_date' => '2024-12-24']);
    $plan = reviewPreview();
    expect($plan['items'][0]['kind'])->toBe('link');
    reviewSourceUpdate($this->source, ['end_date' => null]);
    $plan = reviewPreview();
    expect($plan['items'][0]['access_change'])->toBeTrue()->and($plan['items'][0]['selected'])->toBeFalse();
    reviewApply($plan)->assertSessionHasNoErrors();
    expect($row->fresh()->end_date)->toBeNull();
});

test('an open-ended linked row covers an open-ended duplicate without widening its dates', function (): void {
    reviewSourceUpdate($this->source, ['end_date' => null]);
    $linked = reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio', 'end_date' => null]);
    $duplicate = reviewCandidate(['start_date' => '2025-01-01', 'end_date' => null]);
    reviewApply(reviewPreview())->assertSessionHasNoErrors();
    $this->assertModelMissing($duplicate);
    expect($linked->fresh()->start_date->toDateString())->toBe('2024-01-01')->and($linked->fresh()->end_date)->toBeNull();
});

test('a candidate only partly covered by a linked term is listed, not changed', function (): void {
    reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio']);
    $partial = reviewCandidate(['start_date' => '2024-06-01', 'end_date' => '2025-06-30']);
    $plan = reviewPreview();
    expect($plan['items'])->toBeEmpty()
        ->and(collect($plan['blocked'])->firstWhere('source_id', $partial->id)['reason'])->toBe('partial');
});

test('keeping an orphan does not announce the linked part ending as lost access', function (): void {
    reviewSourceUpdate($this->source, ['end_date' => today()->subDay()->toDateString()]);
    $row = reviewCandidate(['end_date' => null]);
    $plan = reviewPreview();
    reviewApply($plan, null, ['remainder_choices' => [$plan['items'][0]['id'] => 'keep']])->assertSessionHasNoErrors();
    expect(collect(GetRecentAccessChanges::execute($this->holder, 14))->where('kind', GetRecentAccessChanges::ENDED)->where('dutyName', $this->targetDuty->name)->all())->toBeEmpty()
        ->and($row->fresh()->via_dutiable_id)->toBe($this->source->id);
});

test('a source duty reviews only its own pairs on a target shared with other sources', function (): void {
    $otherSource = Duty::factory()->for($this->sourceDuty->institution)->create();
    $otherSource->exOfficioTargetDuties()->attach($this->targetDuty);
    $otherHolder = User::factory()->create();
    reviewFixture([
        'duty_id' => $otherSource->id, 'dutiable_id' => $otherHolder->id,
        'start_date' => '2024-01-01', 'end_date' => '2024-12-31',
    ]);

    $fromSource = asUser($this->manager)->getJson(route('duties.exOfficioReview.preview', $this->sourceDuty))->assertOk()->json('data');
    expect(array_column($fromSource['items'], 'holder_id'))->toBe([$this->holder->id])
        ->and(array_column(reviewPreview()['items'], 'holder_id'))->toContain($this->holder->id, $otherHolder->id);

    $otherItem = collect(reviewPreview()['items'])->firstWhere('holder_id', $otherHolder->id)['id'];
    asUser($this->manager)->post(route('duties.exOfficioReview.apply', $this->sourceDuty), [
        'fingerprint' => $fromSource['fingerprint'], 'item_ids' => [$otherItem],
    ])->assertStatus(422);
    expect(Dutiable::query()->where('dutiable_id', $otherHolder->id)->where('duty_id', $this->targetDuty->id)->exists())->toBeFalse();
});

describe('past seats are only suggested', function (): void {
    test('a term missing from history is proposed but left for the admin to tick', function (): void {
        $item = reviewPreview()['items'][0];

        expect($item['kind'])->toBe('create')
            ->and($item['historical'])->toBeTrue()
            ->and($item['selected'])->toBeFalse();
    });

    test('a link is still pre-ticked, because the term itself proves the seat existed', function (): void {
        reviewCandidate();
        $item = reviewPreview()['items'][0];

        expect($item['kind'])->toBe('link')
            ->and($item['historical'])->toBeFalse()
            ->and($item['selected'])->toBeTrue();
    });
});

describe('permissions begin before a term does', function (): void {
    test('a seat on a source that has not started changes access today', function (): void {
        reviewSourceUpdate($this->source, ['start_date' => today()->addMonth()->toDateString(), 'end_date' => null]);
        $item = reviewPreview()['items'][0];

        expect($item['kind'])->toBe('create')
            ->and($item['access_change'])->toBeTrue()
            ->and($item['selected'])->toBeFalse();
    });

    test('linking two terms that both start later changes nothing today', function (): void {
        $dates = ['start_date' => today()->addMonth()->toDateString(), 'end_date' => null];
        reviewSourceUpdate($this->source, $dates);
        reviewCandidate($dates);
        $item = reviewPreview()['items'][0];

        expect($item['kind'])->toBe('link')
            ->and($item['access_change'])->toBeFalse()
            ->and($item['selected'])->toBeTrue();
    });

    test('linking an ended term to a running source reopens access', function (): void {
        reviewSourceUpdate($this->source, ['start_date' => today()->subDays(30)->toDateString(), 'end_date' => null]);
        reviewCandidate(['start_date' => today()->subDays(30)->toDateString(), 'end_date' => today()->subDays(10)->toDateString()]);
        $item = reviewPreview()['items'][0];

        expect($item['access_change'])->toBeTrue()->and($item['selected'])->toBeFalse();
    });
});

test('a new seat that counts toward a quota says so', function (): void {
    $this->targetDuty->assignableTenants()->attach($this->tenant);
    reviewSourceUpdate($this->source, ['end_date' => null]);
    $item = reviewPreview()['items'][0];

    expect($item['kind'])->toBe('create')
        ->and($item['quota_change'])->toBeTrue()
        ->and($item['tenant_after_name'])->toBe($this->tenant->shortname);
});

test('an ended new seat takes up no quota', function (): void {
    $this->targetDuty->assignableTenants()->attach($this->tenant);
    reviewSourceUpdate($this->source, ['end_date' => '2024-12-31']);

    expect(reviewPreview()['items'][0]['quota_change'])->toBeFalse();
});

describe('a duplicate has to be the same seat', function (): void {
    test('a linked term under another tenant does not make a term a duplicate', function (): void {
        $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $this->targetDuty->assignableTenants()->attach($other);
        reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio', 'tenant_id' => null]);
        $kept = reviewCandidate(['tenant_id' => $other->id]);
        $plan = reviewPreview();

        expect(array_column($plan['items'], 'kind'))->not->toContain('remove_duplicate')
            ->and(collect($plan['blocked'])->firstWhere('source_id', $kept->id)['reason'])->toBe('partial');
    });

    test('the same tenant on both is still proposed for deletion', function (): void {
        reviewCandidate(['via_dutiable_id' => $this->source->id, 'assignment_origin' => 'ex_officio']);
        reviewCandidate();

        expect(array_column(reviewPreview()['items'], 'kind'))->toBe(['remove_duplicate']);
    });
});

describe('no recorded day is dropped', function (): void {
    test('a few days before the source stay as their own term', function (): void {
        $row = reviewCandidate(['start_date' => '2023-12-28', 'end_date' => '2024-12-31']);
        reviewApply(reviewPreview())->assertSessionHasNoErrors();

        $kept = Dutiable::query()->where('duty_id', $this->targetDuty->id)->where('id', '!=', $row->id)->sole();
        expect($kept->start_date->toDateString())->toBe('2023-12-28')
            ->and($kept->end_date->toDateString())->toBe('2023-12-31')
            ->and($kept->assignment_origin)->toBe('manual')
            ->and($row->fresh()->via_dutiable_id)->toBe($this->source->id);
    });

    test('a few days after an ended source stay as their own term', function (): void {
        $row = reviewCandidate(['start_date' => '2024-01-01', 'end_date' => '2025-01-03']);
        reviewApply(reviewPreview())->assertSessionHasNoErrors();

        $kept = Dutiable::query()->where('duty_id', $this->targetDuty->id)->where('id', '!=', $row->id)->sole();
        expect($kept->start_date->toDateString())->toBe('2025-01-01')
            ->and($kept->end_date->toDateString())->toBe('2025-01-03');
    });

    test('a single day is enough to keep', function (): void {
        reviewCandidate(['start_date' => '2023-12-31', 'end_date' => '2024-12-31']);

        expect(reviewPreview()['items'][0]['remainders'])->toBe([['start_date' => '2023-12-31', 'end_date' => '2023-12-31']]);
    });

    test('ending an orphan with its source remains an explicit way to drop the rest', function (): void {
        reviewSourceUpdate($this->source, ['end_date' => today()->subDays(3)->toDateString()]);
        $row = reviewCandidate(['end_date' => null]);
        reviewApply(reviewPreview())->assertSessionHasNoErrors();

        expect(Dutiable::query()->where('duty_id', $this->targetDuty->id)->count())->toBe(1)
            ->and($row->fresh()->end_date->toDateString())->toBe(today()->subDays(3)->toDateString());
    });
});
