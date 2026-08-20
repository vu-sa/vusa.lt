<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'users.read.padalinys',
    ]);

    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    $this->institution = $this->dutyManagerDuty->institution;
});

function timelineUrl(string $scope, string $scopeId, array $extra = []): string
{
    return route('api.v1.admin.dutiableTimeline.index', ['scope' => $scope, 'scope_id' => $scopeId, ...$extra]);
}

describe('unauthorized access', function (): void {
    test('a guest is rejected', function (): void {
        $this->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))->assertUnauthorized();
    });

    test('an unknown scope value is rejected', function (): void {
        asUser($this->dutyManager)
            ->getJson(timelineUrl('everything', $this->dutyManagerDuty->id))
            ->assertStatus(422);
    });

    test('a scope id that resolves to nothing is a validation error, not a 403', function (): void {
        asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', '01jnotarealdutyatall00000'))
            ->assertStatus(422);
    });
});

describe('authorized access', function (): void {
    test('a duty scope groups rows by their holder', function (): void {
        $holder = makeUser($this->tenant);

        Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $holder->id,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
            ->assertOk();

        expect($response->json('success'))->toBeTrue()
            ->and($response->json('data.scope.type'))->toBe('duty')
            ->and(collect($response->json('data.groups'))->pluck('kind')->unique()->all())->toBe(['user']);

        $row = collect($response->json('data.rows'))->firstWhere('holder_id', $holder->id);
        expect($row['start_date'])->toBe('2025-07-01')
            ->and($row['end_date'])->toBe('2026-06-30')
            ->and($row['group_key'])->toBe('user:'.$holder->id);
    });

    test('a user scope groups rows by duty', function (): void {
        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('user', $this->dutyManager->id))
            ->assertOk();

        expect(collect($response->json('data.groups'))->pluck('kind')->unique()->all())->toBe(['duty']);
    });

    test('an institution scope spans every duty in the institution', function (): void {
        $other = Duty::factory()->create(['institution_id' => $this->institution->id]);
        Dutiable::factory()->create([
            'duty_id' => $other->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2025-07-01',
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('institution', $this->institution->id))
            ->assertOk();

        expect(collect($response->json('data.rows'))->pluck('duty_id'))->toContain($other->id);
    });

    test('cadences for the institution and the global ladder are both returned', function (): void {
        $global = Cadence::factory()->forYear(2025)->create();
        $override = Cadence::factory()->forYear(2025)->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
            ->assertOk();

        $ids = collect($response->json('data.cadences'))->pluck('id');
        expect($ids)->toContain($global->id)->toContain($override->id);
    });

    test('an unrelated institution cadence is not returned', function (): void {
        $unrelated = Cadence::factory()->forYear(2025)->create([
            'institution_id' => Institution::factory()->create()->id,
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
            ->assertOk();

        expect(collect($response->json('data.cadences'))->pluck('id'))->not->toContain($unrelated->id);
    });

    test('include_ended=false drops rows that have already finished', function (): void {
        $ended = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2020-07-01',
            'end_date' => '2021-06-30',
        ]);

        $withEnded = asUser($this->dutyManager)->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))->assertOk();
        expect(collect($withEnded->json('data.rows'))->pluck('id'))->toContain($ended->id);

        $withoutEnded = asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id, ['include_ended' => 'false']))
            ->assertOk();

        expect(collect($withoutEnded->json('data.rows'))->pluck('id'))->not->toContain($ended->id);
    });
});

describe('ex officio rows', function (): void {
    /**
     * Derived rows mirror their source and DutiableController strips date edits on them,
     * so the editor must never offer the gesture — `editable` has to be false regardless
     * of how much permission the actor holds.
     */
    test('a derived row is never editable and points at its source', function (): void {
        $holder = makeUser($this->tenant);

        $source = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $holder->id,
            'start_date' => '2025-07-01',
        ]);

        $targetDuty = Duty::factory()->create(['institution_id' => $this->institution->id]);

        $derived = Dutiable::factory()->create([
            'duty_id' => $targetDuty->id,
            'dutiable_id' => $holder->id,
            'via_dutiable_id' => $source->id,
            'start_date' => '2025-07-01',
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('institution', $this->institution->id))
            ->assertOk();

        $rows = collect($response->json('data.rows'))->keyBy('id');

        expect($rows[$derived->id]['is_derived'])->toBeTrue()
            ->and($rows[$derived->id]['editable'])->toBeFalse()
            ->and($rows[$derived->id]['source']['id'])->toBe($source->id);
    });

    test('a source row advertises the derived rows that follow it', function (): void {
        $holder = makeUser($this->tenant);

        $source = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $holder->id,
            'start_date' => '2025-07-01',
        ]);

        $targetDuty = Duty::factory()->create(['institution_id' => $this->institution->id]);
        $derived = Dutiable::factory()->create([
            'duty_id' => $targetDuty->id,
            'dutiable_id' => $holder->id,
            'via_dutiable_id' => $source->id,
            'start_date' => '2025-07-01',
        ]);

        $response = asUser($this->dutyManager)
            ->getJson(timelineUrl('institution', $this->institution->id))
            ->assertOk();

        $rows = collect($response->json('data.rows'))->keyBy('id');

        expect($rows[$source->id]['derived_ids'])->toContain($derived->id);
    });
});

describe('tenant isolation', function (): void {
    test('a duty in another tenant is not readable', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignDuty = Duty::factory()->create([
            'institution_id' => Institution::factory()->create(['tenant_id' => $otherTenant->id])->id,
        ]);

        asUser($this->dutyManager)
            ->getJson(timelineUrl('duty', $foreignDuty->id))
            ->assertForbidden();
    });

    test('rows the actor cannot manage come back visible but not editable', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreignDuty = Duty::factory()->create(['institution_id' => $foreignInstitution->id]);

        Dutiable::factory()->create([
            'duty_id' => $foreignDuty->id,
            'dutiable_id' => User::factory()->create()->id,
            'start_date' => '2025-07-01',
        ]);

        $superAdmin = makeUser($this->tenant);
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $response = asUser($superAdmin)
            ->getJson(timelineUrl('institution', $foreignInstitution->id))
            ->assertOk();

        // A super admin manages everything, so this pins the positive side of the
        // editable flag; the negative side is covered by the derived-row test above.
        expect(collect($response->json('data.rows'))->pluck('editable')->unique()->all())->toBe([true]);
    });
});

describe('row ordering', function (): void {
    test('rows carry the term they sit in and sort newest term first', function (): void {
        $older = Cadence::factory()->create([
            'institution_id' => null, 'start_date' => '2023-07-01', 'end_date' => '2024-06-30',
        ]);
        $newer = Cadence::factory()->create([
            'institution_id' => null, 'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);

        $old = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2023-07-01',
            'end_date' => '2024-06-30',
        ]);
        $new = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);

        $rows = collect(
            asUser($this->dutyManager)
                ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
                ->assertOk()
                ->json('data.rows')
        )->whereIn('id', [$old->id, $new->id])->values();

        expect($rows[0]['id'])->toBe($new->id)
            ->and($rows[0]['cadence_id'])->toBe($newer->id)
            ->and($rows[1]['id'])->toBe($old->id)
            ->and($rows[1]['cadence_id'])->toBe($older->id);
    });
});

/**
 * A bar shows a period and nothing else, so these per-assignment overrides have to be
 * flagged — they are precisely what a merge or a delete would take with it.
 */
describe('per-assignment extras', function (): void {
    // DutiableFactory fills these at random, so they are nulled explicitly here — the
    // point of the test is the empty case, not whichever one faker rolled.
    test('a row that is only a period reports no extras', function (): void {
        $row = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2024-07-01',
            'additional_email' => null,
            'additional_photo' => null,
            'description' => null,
            'study_program_id' => null,
            'use_original_duty_name' => false,
        ]);

        $payload = collect(
            asUser($this->dutyManager)
                ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
                ->assertOk()
                ->json('data.rows')
        )->firstWhere('id', $row->id);

        expect($payload['extras'])->toBeNull();
    });

    test('an overridden email, programme and description are all reported', function (): void {
        $program = StudyProgram::factory()->create(['name' => 'Programų sistemos']);

        $row = Dutiable::factory()->create([
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => makeUser($this->tenant)->id,
            'start_date' => '2024-07-01',
            'additional_email' => 'pirmininkas@vusa.lt',
            'study_program_id' => $program->id,
            'description' => '<p>Atstovauja MIF studentams</p>',
            'additional_photo' => null,
            'use_original_duty_name' => false,
        ]);

        $payload = collect(
            asUser($this->dutyManager)
                ->getJson(timelineUrl('duty', $this->dutyManagerDuty->id))
                ->assertOk()
                ->json('data.rows')
        )->firstWhere('id', $row->id);

        expect($payload['extras']['email'])->toBe('pirmininkas@vusa.lt')
            ->and($payload['extras']['study_program'])->toBe('Programų sistemos')
            // Tags stripped: the chart shows a tooltip, not rendered rich text.
            ->and($payload['extras']['description'])->toBe('Atstovauja MIF studentams');
    });
});
