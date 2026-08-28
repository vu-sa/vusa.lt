<?php

use App\Models\Cadence;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys', 'users.read.padalinys']);

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

function previewTimeline(array $operations)
{
    return asUser(test()->manager)->postJson(route('api.v1.admin.dutiableTimeline.preview'), [
        'operations' => $operations,
    ]);
}

test('a guest is rejected', function (): void {
    $this->postJson(route('api.v1.admin.dutiableTimeline.preview'), [
        'operations' => [['type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2024-07-18']],
    ])->assertUnauthorized();
});

test('the preview writes nothing', function (): void {
    previewTimeline([['type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2024-11-18']])->assertOk();

    expect($this->row->fresh()->start_date->toDateString())->toBe('2024-05-18');
});

test('it reports the concrete before and after', function (): void {
    $response = previewTimeline([[
        'type' => 'set_dates', 'row_ids' => [$this->row->id],
        'start_date' => '2024-07-18', 'end_date' => '2025-07-17',
    ]])->assertOk();

    $change = collect($response->json('data.changes'))->firstWhere('row_id', $this->row->id);

    expect($change['before'])->toBe(['start_date' => '2024-05-18', 'end_date' => '2025-05-17'])
        ->and($change['after'])->toBe(['start_date' => '2024-07-18', 'end_date' => '2025-07-17'])
        ->and($change['reasons'])->toBe(['set_dates'])
        ->and($response->json('data.summary.changed'))->toBe(1);
});

test('a row the operations leave alone is listed as unchanged, not as a change', function (): void {
    $cadence = Cadence::factory()->create([
        'institution_id' => null, 'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
    ]);

    $response = previewTimeline([[
        'type' => 'align_to_cadence',
        'row_ids' => [$this->row->id],
        'cadence_id' => $cadence->id,
        'edges' => 'start',
        'threshold_days' => 3,
    ]])->assertOk();

    expect($response->json('data.changes'))->toBe([])
        ->and($response->json('data.unchanged_row_ids'))->toBe([$this->row->id]);
});

test('a derived row is previewed as blocked rather than dropped', function (): void {
    $derived = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->holder->id,
        'via_dutiable_id' => $this->row->id,
        'start_date' => '2024-05-18',
    ]);

    $response = previewTimeline([[
        'type' => 'set_dates', 'row_ids' => [$derived->id], 'start_date' => '2024-06-18',
    ]])->assertOk();

    $change = collect($response->json('data.changes'))->firstWhere('row_id', $derived->id);

    expect($change['blocked'])->toBe('derived')
        ->and($response->json('data.summary.blocked'))->toBe(1)
        ->and($response->json('data.summary.changed'))->toBe(0);
});

test('a source row projects the ex-officio seats that will follow it', function (): void {
    $derived = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => $this->holder->id,
        'via_dutiable_id' => $this->row->id,
        'start_date' => '2024-05-18',
        'end_date' => '2025-05-17',
    ]);

    $response = previewTimeline([[
        'type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2024-06-18',
    ]])->assertOk();

    $change = collect($response->json('data.changes'))->firstWhere('row_id', $this->row->id);

    expect($change['derived'])->toHaveCount(1)
        ->and($change['derived'][0]['id'])->toBe($derived->id)
        ->and($change['derived'][0]['start_date'])->toBe('2024-06-18');
});

test('touching the actor own row flags the batch as self affecting', function (): void {
    $ownRow = $this->manager->duties()->first()->pivot;

    $response = previewTimeline([[
        'type' => 'set_dates', 'row_ids' => [$ownRow->id], 'start_date' => '2024-06-18',
    ]])->assertOk();

    expect($response->json('data.self_affecting'))->toBeTrue();
});

test('someone else row is not self affecting', function (): void {
    $response = previewTimeline([[
        'type' => 'set_dates', 'row_ids' => [$this->row->id], 'start_date' => '2024-06-18',
    ]])->assertOk();

    expect($response->json('data.self_affecting'))->toBeFalse();
});
