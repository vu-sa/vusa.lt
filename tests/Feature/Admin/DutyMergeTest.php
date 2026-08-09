<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
    ]);

    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    $this->institution = $this->dutyManagerDuty->institution;

    $this->target = Duty::factory()->create([
        'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communications Coordinator'],
        'institution_id' => $this->institution->id,
    ]);
    $this->source = Duty::factory()->create([
        'name' => ['lt' => 'Komunikacijos koordinatorė', 'en' => 'Communications Coordinator (f)'],
        'institution_id' => $this->institution->id,
    ]);
});

describe('unauthorized access', function (): void {
    test('cannot merge without duties permissions', function (): void {
        $outsider = makeUser($this->tenant);

        $response = asUser($outsider)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        expect($response->status())->toBe(403);
        expect($this->source->fresh()->trashed())->toBeFalse();
    });
});

describe('merging assignments', function (): void {
    test('moves dutiables onto the kept duty and soft-deletes the source', function (): void {
        Dutiable::factory()->forDuty($this->source)->ended()->create();
        Dutiable::factory()->forDuty($this->source)->active()->create();

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ])->assertRedirect(route('duties.edit', $this->target));

        expect(Dutiable::where('duty_id', $this->source->id)->count())->toBe(0)
            ->and(Dutiable::where('duty_id', $this->target->id)->count())->toBe(2)
            ->and($this->source->fresh()->trashed())->toBeTrue();
    });

    test('collapses overlapping assignments for the same person into one, backfilling missing fields', function (): void {
        $person = User::factory()->create();

        Dutiable::factory()->forDuty($this->target)->forUser($person)->create([
            'start_date' => '2024-01-01',
            'end_date' => null, // still current
            'study_program_id' => null,
        ]);

        $studyProgram = StudyProgram::factory()->for($this->tenant)->create();
        Dutiable::factory()->forDuty($this->source)->forUser($person)->create([
            'start_date' => '2024-06-01',
            'end_date' => '2024-12-01',
            'study_program_id' => $studyProgram->id,
        ]);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        $rows = Dutiable::where('duty_id', $this->target->id)->where('dutiable_id', $person->id)->get();

        expect($rows)->toHaveCount(1);
        $survivor = $rows->first();
        expect($survivor->start_date->toDateString())->toBe('2024-01-01')
            // The target's row was open-ended, which already covers everything after it started —
            // that stays true regardless of what the (now redundant) source row's end date was.
            ->and($survivor->end_date)->toBeNull()
            // The target's row had no study program; the source's is backfilled onto the survivor.
            ->and($survivor->study_program_id)->toBe($studyProgram->id);
    });

    test('does not collapse two genuinely separate stints for the same person', function (): void {
        $person = User::factory()->create();

        Dutiable::factory()->forDuty($this->target)->forUser($person)->create([
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
        ]);
        Dutiable::factory()->forDuty($this->source)->forUser($person)->create([
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
        ]);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        expect(Dutiable::where('duty_id', $this->target->id)->where('dutiable_id', $person->id)->count())->toBe(2);
    });
});

describe('merging related pivots', function (): void {
    test('moves duty_tenant quotas, preferring the more permissive (null) quota', function (): void {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);

        DB::table('duty_tenant')->insert([
            ['duty_id' => $this->target->id, 'tenant_id' => $otherTenant->id, 'quota' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('duty_tenant')->insert([
            ['duty_id' => $this->source->id, 'tenant_id' => $otherTenant->id, 'quota' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        $rows = DB::table('duty_tenant')->where('duty_id', $this->target->id)->where('tenant_id', $otherTenant->id)->get();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()->quota)->toBeNull()
            ->and(DB::table('duty_tenant')->where('duty_id', $this->source->id)->count())->toBe(0);
    });

    test('drops an ex-officio link that would become self-referencing after the merge', function (): void {
        $third = Duty::factory()->create(['institution_id' => $this->institution->id]);

        // target holds ex-officio over source (would become target -> target)
        DB::table('ex_officio_duties')->insert([
            'source_duty_id' => $this->target->id,
            'target_duty_id' => $this->source->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // source separately holds ex-officio over an unrelated third duty
        DB::table('ex_officio_duties')->insert([
            'source_duty_id' => $this->source->id,
            'target_duty_id' => $third->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        expect(DB::table('ex_officio_duties')->where('source_duty_id', $this->target->id)->where('target_duty_id', $this->target->id)->exists())->toBeFalse()
            ->and(DB::table('ex_officio_duties')->where('source_duty_id', $this->target->id)->where('target_duty_id', $third->id)->exists())->toBeTrue();
    });

    test('moves types onto the kept duty without duplicating a type both already share', function (): void {
        $shared = Type::factory()->create(['model_type' => Duty::class]);
        $onlyOnSource = Type::factory()->create(['model_type' => Duty::class]);

        $this->target->types()->attach($shared->id);
        $this->source->types()->attach([$shared->id, $onlyOnSource->id]);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        $keptTypeIds = $this->target->fresh()->types()->pluck('types.id')->all();

        expect($keptTypeIds)->toContain($shared->id, $onlyOnSource->id)
            ->and(DB::table('typeables')->where('typeable_id', $this->source->id)->count())->toBe(0);
    });

    test('moves admin roles onto the kept duty', function (): void {
        $extraRole = Role::firstOrCreate(['name' => 'Resource Manager', 'guard_name' => 'web']);
        $this->source->assignRole($extraRole);

        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        expect($this->target->fresh()->hasRole($extraRole))->toBeTrue();
    });
});

describe('after merging', function (): void {
    test('the source duty is soft-deleted, not force-deleted, and can be restored', function (): void {
        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->source->id],
        ]);

        expect(Duty::withTrashed()->find($this->source->id))->not->toBeNull()
            ->and(Duty::find($this->source->id))->toBeNull();
    });

    test('rejects a source id equal to the target', function (): void {
        asUser($this->dutyManager)->post(route('duties.mergeDuties'), [
            'target_duty_id' => $this->target->id,
            'source_duty_ids' => [$this->target->id],
        ])->assertSessionHasErrors('source_duty_ids.0');
    });
});
