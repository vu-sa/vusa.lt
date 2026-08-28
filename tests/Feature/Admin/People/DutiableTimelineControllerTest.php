<?php

use App\Events\DutiableChanged;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.update.padalinys',
        'users.read.padalinys',
        // The page and the API both authorize `view` on the scope institution; the four
        // seeded roles that hold duties.update.padalinys all hold this one too.
        'institutions.read.padalinys',
    ]);

    $this->manager = makeUser($this->tenant);
    $this->duty = $this->manager->duties()->first();
    $this->duty->assignRole('Communication Coordinator');

    $this->holder = makeUser($this->tenant);

    $this->row = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->holder->id,
        'start_date' => '2024-05-18',
        'end_date' => '2025-05-17',
    ]);
});

function applyTimeline(array $operations, array $extra = []): array
{
    return ['operations' => $operations, ...$extra];
}

describe('authorization', function (): void {
    test('a guest is redirected to login', function (): void {
        $this->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2024-07-01',
        ]]))->assertRedirect(route('login'));
    });

    test('a batch containing one unmanageable row is refused whole', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first()
            ?? Tenant::factory()->create();

        $stranger = makeUser($otherTenant);
        $strangerRow = Dutiable::factory()->create([
            'duty_id' => $stranger->duties()->first()->id,
            'dutiable_id' => $stranger->id,
            'start_date' => '2024-07-01',
        ]);

        asUser($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates',
            'row_ids' => [$this->row->id, $strangerRow->id],
            'start_date' => '2024-09-01',
        ]]))->assertForbidden();

        expect($this->row->fresh()->start_date->toDateString())->toBe('2024-05-18')
            ->and($strangerRow->fresh()->start_date->toDateString())->toBe('2024-07-01');
    });
});

describe('the standalone page', function (): void {
    test('a guest is redirected to login', function (): void {
        $this->get(route('dutiables.timeline'))->assertRedirect(route('login'));
    });

    test('someone who may read duties gets the page', function (): void {
        asUser($this->manager)->get(route('dutiables.timeline'))->assertOk();
    });

    test('an institution in the query string preloads the scope', function (): void {
        $institution = $this->duty->institution;

        // Plain GET, not asUserWithInertia(): the stub Inertia version header makes the
        // middleware answer 409 on a page request rather than rendering it.
        asUser($this->manager)
            ->get(route('dutiables.timeline', ['institution' => $institution->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/DutiableTimeline')
                ->where('initialInstitution.id', $institution->id));
    });

    test('an institution id that does not exist is a validation error, not a 500', function (): void {
        asUser($this->manager)
            ->get(route('dutiables.timeline', ['institution' => '01jnotarealinstitution000']))
            ->assertSessionHasErrors('institution');
    });

    test('with no query string the page opens on the institution the actor sits in', function (): void {
        asUser($this->manager)
            ->get(route('dutiables.timeline'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('initialInstitution.id', $this->duty->institution_id)
                ->where('userInstitutions.0.id', $this->duty->institution_id));
    });

    test('a second duty in the same institution ranks it above one held once', function (): void {
        // Someone with several duties is the ordinary case, so the default is a ranking:
        // the body they are busiest in wins, and the rest stay one click away in the menu.
        $secondary = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $this->manager->duties()->attach($secondary, ['start_date' => now()->subDay()]);
        $this->manager->duties()->attach(
            Duty::factory()->for($this->duty->institution)->create(),
            ['start_date' => now()->subDay()],
        );

        asUser($this->manager)
            ->get(route('dutiables.timeline'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('initialInstitution.id', $this->duty->institution_id)
                ->where('userInstitutions.0.id', $this->duty->institution_id)
                ->count('userInstitutions', 2));
    });

    test('an institution the actor may not view is left out of the shortcuts', function (): void {
        // `institutions.read.padalinys` is tenant-scoped, so a seat in another tenant's body
        // would otherwise be offered and then 403 on the first fetch.
        $foreign = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
        $this->manager->duties()->attach(
            Duty::factory()->for(Institution::factory()->for($foreign))->create(),
            ['start_date' => now()->subDay()],
        );

        asUser($this->manager)
            ->get(route('dutiables.timeline'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('initialInstitution.id', $this->duty->institution_id)
                ->count('userInstitutions', 1));
    });

    test('an ended duty does not decide the default scope', function (): void {
        $ended = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $this->manager->duties()->attach($ended, [
            'start_date' => now()->subYears(2),
            'end_date' => now()->subYear(),
        ]);

        asUser($this->manager)
            ->get(route('dutiables.timeline'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('initialInstitution.id', $this->duty->institution_id)
                ->count('userInstitutions', 1));
    });
});

describe('validation', function (): void {
    test('an unknown operation type is rejected', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'teleport', 'row_ids' => [$this->row->id],
        ]]))->assertSessionHasErrors('operations.0.type');
    });

    test('close_open_ended without an end date is rejected', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'close_open_ended', 'row_ids' => [$this->row->id],
        ]]))->assertSessionHasErrors('operations.0.end_date');
    });

    test('align_to_cadence without a cadence is accepted and resolves per edge', function (): void {
        Cadence::factory()->create([
            'institution_id' => null, 'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'align_to_cadence', 'row_ids' => [$this->row->id],
        ]]))->assertRedirect()->assertSessionHasNoErrors();
    });
});

describe('applying', function (): void {
    test('set_dates writes the literal dates, with no off-by-one', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates',
            'row_ids' => [$this->row->id],
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ]]))->assertRedirect();

        $fresh = $this->row->fresh();
        expect($fresh->start_date->toDateString())->toBe('2025-05-18')
            ->and($fresh->end_date->toDateString())->toBe('2026-05-17');
    });

    test('an explicit null end date makes a row open-ended', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates', 'row_ids' => [$this->row->id], 'end_date' => null,
        ]]))->assertRedirect();

        expect($this->row->fresh()->end_date)->toBeNull();
    });

    /**
     * The whole point of an omitted `cadence_id`: a row covering two terms is straightened
     * against the term each of its edges actually sits in, not against the start's.
     */
    test('align_to_cadence with no cadence aligns each edge to its own term', function (): void {
        Cadence::factory()->create([
            'institution_id' => null, 'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);
        Cadence::factory()->create([
            'institution_id' => null, 'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $spanning = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2024-07-05',
            'end_date' => '2026-06-20',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'align_to_cadence', 'row_ids' => [$spanning->id],
        ]]))->assertRedirect();

        $fresh = $spanning->fresh();
        expect($fresh->start_date->toDateString())->toBe('2024-07-01')
            ->and($fresh->end_date->toDateString())->toBe('2026-06-30');
    });

    test('operations fold in order', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([
            ['type' => 'set_dates', 'row_ids' => [$this->row->id], 'end_date' => null],
            ['type' => 'close_open_ended', 'row_ids' => [$this->row->id], 'end_date' => '2025-06-30'],
        ]))->assertRedirect();

        expect($this->row->fresh()->end_date->toDateString())->toBe('2025-06-30');
    });

    test('align_to_cadence leaves a row further out than the threshold alone', function (): void {
        $cadence = Cadence::factory()->create([
            'institution_id' => null,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);

        $farOut = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2024-01-15',
            'end_date' => '2025-06-30',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'align_to_cadence',
            'row_ids' => [$this->row->id, $farOut->id],
            'cadence_id' => $cadence->id,
            'edges' => 'start',
            'threshold_days' => 45,
        ]]))->assertRedirect();

        expect($this->row->fresh()->start_date->toDateString())->toBe('2024-07-01')
            ->and($farOut->fresh()->start_date->toDateString())->toBe('2024-01-15');
    });

    test('close_open_ended only touches rows without an end date', function (): void {
        $open = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2024-07-01',
            'end_date' => null,
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'close_open_ended',
            'row_ids' => [$this->row->id, $open->id],
            'end_date' => '2025-06-30',
        ]]))->assertRedirect();

        expect($open->fresh()->end_date->toDateString())->toBe('2025-06-30')
            ->and($this->row->fresh()->end_date->toDateString())->toBe('2025-05-17');
    });

    test('each written row fires its own DutiableChanged event', function (): void {
        $second = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2024-07-01',
        ]);

        // Faked after the fixture, so the count is only what the endpoint itself wrote.
        Event::fake([DutiableChanged::class]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates', 'row_ids' => [$this->row->id, $second->id], 'start_date' => '2024-06-18',
        ]]))->assertRedirect();

        Event::assertDispatchedTimes(DutiableChanged::class, 2);
    });
});

describe('rows that must not be written', function (): void {
    test('a derived ex-officio row is reported as blocked and left untouched', function (): void {
        $derived = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'via_dutiable_id' => $this->row->id,
            'start_date' => '2024-05-18',
            'end_date' => '2025-05-17',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates', 'row_ids' => [$derived->id], 'start_date' => '2024-08-18',
        ]]))->assertRedirect()->assertSessionHas('info');

        expect($derived->fresh()->start_date->toDateString())->toBe('2024-05-18');
    });

    test('a move that would end a row before it starts is blocked', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2026-01-01',
        ]]))->assertRedirect()->assertSessionHas('info');

        expect($this->row->fresh()->start_date->toDateString())->toBe('2024-05-18');
    });
});

describe('self-lockout guard', function (): void {
    test('a batch touching the actor own duty is rolled back until acknowledged', function (): void {
        $ownRow = $this->manager->duties()->first()->pivot;

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates',
            'row_ids' => [$ownRow->id],
            'start_date' => '2019-01-01',
            'end_date' => '2019-12-31',
        ]]))->assertRedirect();

        // The role reaches the actor only through this duty, so ending it in the past
        // costs them the role — the guard must refuse and persist nothing.
        expect(Dutiable::find($ownRow->id)->start_date->toDateString())->not->toBe('2019-01-01');
    });

    test('acknowledging the warning persists the change', function (): void {
        $ownRow = $this->manager->duties()->first()->pivot;

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.apply'), applyTimeline([[
            'type' => 'set_dates',
            'row_ids' => [$ownRow->id],
            'start_date' => '2019-01-01',
            'end_date' => '2019-12-31',
        ]], ['acknowledge_access_change' => true]))->assertRedirect();

        expect(Dutiable::find($ownRow->id)->start_date->toDateString())->toBe('2019-01-01');
    });
});

describe('removing a row from the timeline', function (): void {
    test('the stay flag keeps the caller where it is', function (): void {
        asUserWithInertia($this->manager)
            ->from(route('dutiables.timeline'))
            ->delete(route('dutiables.destroy', $this->row), ['stay' => true])
            ->assertRedirect(route('dutiables.timeline'));

        expect(Dutiable::query()->whereKey($this->row->id)->exists())->toBeFalse();
    });

    test('without it the dutiable edit page still leaves for the user', function (): void {
        asUserWithInertia($this->manager)
            ->delete(route('dutiables.destroy', $this->row))
            ->assertRedirect(route('users.edit', $this->holder));

        expect(Dutiable::query()->whereKey($this->row->id)->exists())->toBeFalse();
    });
});

describe('merging stints', function (): void {
    beforeEach(function (): void {
        $this->earlier = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2022-07-01',
            'end_date' => '2023-06-30',
        ]);

        $this->later = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'start_date' => '2023-09-01',
            'end_date' => '2024-06-30',
        ]);
    });

    test('two stints of one holder fold into the earliest row', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $this->later->id],
        ])->assertRedirect();

        $survivor = $this->earlier->fresh();

        expect($survivor)->not->toBeNull()
            ->and($survivor->start_date->toDateString())->toBe('2022-07-01')
            ->and($survivor->end_date->toDateString())->toBe('2024-06-30')
            ->and(Dutiable::query()->whereKey($this->later->id)->exists())->toBeFalse();
    });

    /** An open end swallows any dated one — the seat is still held. */
    test('an open-ended stint makes the merged row open-ended', function (): void {
        $this->later->update(['end_date' => null]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $this->later->id],
        ])->assertRedirect();

        expect($this->earlier->fresh()->end_date)->toBeNull();
    });

    test('the survivor inherits details the earlier row was missing', function (): void {
        $this->later->update(['additional_email' => 'pirmininkas@vusa.lt']);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $this->later->id],
        ])->assertRedirect();

        expect($this->earlier->fresh()->additional_email)->toBe('pirmininkas@vusa.lt');
    });

    test('two different holders cannot be merged', function (): void {
        $someoneElse = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2023-09-01',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $someoneElse->id],
        ])->assertSessionHasErrors('row_ids');

        expect(Dutiable::query()->whereKey($someoneElse->id)->exists())->toBeTrue();
    });

    /** A derived row's dates are its source's; folding one would strand that sync. */
    test('a derived row cannot be merged', function (): void {
        $derived = Dutiable::factory()->create([
            'duty_id' => $this->duty->id,
            'dutiable_id' => $this->holder->id,
            'via_dutiable_id' => $this->row->id,
            'start_date' => '2023-09-01',
        ]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $derived->id],
        ])->assertSessionHasErrors('row_ids');
    });

    test('a single row is not a merge', function (): void {
        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id],
        ])->assertSessionHasErrors('row_ids');
    });

    // Direct request, not `asUserWithInertia`: an Inertia one answers a refusal with a
    // 302 and an error flash, which is the contract but not what is asserted here.
    test('a user who may not manage the rows is refused', function (): void {
        asUser(makeUser($this->tenant))->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $this->later->id],
        ])->assertForbidden();

        expect(Dutiable::query()->whereKey($this->later->id)->exists())->toBeTrue();
    });

    test('each removed row fires its own DutiableChanged event', function (): void {
        Event::fake([DutiableChanged::class]);

        asUserWithInertia($this->manager)->post(route('dutiables.timeline.merge'), [
            'row_ids' => [$this->earlier->id, $this->later->id],
        ])->assertRedirect();

        Event::assertDispatched(DutiableChanged::class);
    });
});
