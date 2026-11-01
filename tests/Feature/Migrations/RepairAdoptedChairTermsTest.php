<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

const REPAIR_CHAIR_ROW = '01ktm6vay1k6fy64hzxkvh5kv5';
const REPAIR_DEANERY_ROW = '01m574d988aq4nx01mmfw8h358';

function runChairRepair(): void
{
    (require base_path('database/migrations/2026_10_31_120000_repair_adopted_chair_terms.php'))->up();
}

beforeEach(function (): void {
    $this->holder = User::factory()->create();
    $this->chairDuty = Duty::factory()->create();
    $this->deaneryDuty = Duty::factory()->create();
    $this->councilDuty = Duty::factory()->create();
    $this->chairDuty->exOfficioTargetDuties()->attach([$this->deaneryDuty->id, $this->councilDuty->id]);

    $this->deanery = Dutiable::factory()->create([
        'id' => REPAIR_DEANERY_ROW,
        'duty_id' => $this->deaneryDuty->id,
        'dutiable_id' => $this->holder->id,
        'start_date' => '2026-07-01',
        'end_date' => null,
        'assignment_origin' => 'unknown',
    ]);
    $this->chair = Dutiable::factory()->create([
        'id' => REPAIR_CHAIR_ROW,
        'duty_id' => $this->chairDuty->id,
        'dutiable_id' => $this->holder->id,
        'via_dutiable_id' => $this->deanery->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-10-31',
        'assignment_origin' => 'ex_officio',
    ]);
    $this->council = Dutiable::factory()->create([
        'duty_id' => $this->councilDuty->id,
        'dutiable_id' => $this->holder->id,
        'via_dutiable_id' => $this->chair->id,
        'start_date' => '2026-06-01',
        'end_date' => null,
        'assignment_origin' => 'ex_officio',
    ]);
});

test('restores the chair as an open term that the deanery seat and its other seats follow', function (): void {
    runChairRepair();

    $chair = $this->chair->fresh();
    $deanery = $this->deanery->fresh();
    $council = $this->council->fresh();

    expect($chair->via_dutiable_id)->toBeNull()
        ->and($chair->assignment_origin)->toBe('manual')
        ->and($chair->start_date->toDateString())->toBe('2026-06-01')
        ->and($chair->end_date)->toBeNull()
        ->and($deanery->via_dutiable_id)->toBe($chair->id)
        ->and($deanery->assignment_origin)->toBe('ex_officio')
        ->and($deanery->start_date->toDateString())->toBe('2026-06-01')
        ->and($deanery->end_date)->toBeNull()
        ->and($council->via_dutiable_id)->toBe($chair->id)
        ->and($council->start_date->toDateString())->toBe('2026-06-01')
        ->and($council->end_date)->toBeNull();

    expect(Dutiable::query()->where('dutiable_id', $this->holder->id)->count())->toBe(3);
});

test('leaves the deanery term independent when the corrected rule was removed', function (): void {
    $this->chairDuty->exOfficioTargetDuties()->detach($this->deaneryDuty->id);

    runChairRepair();

    expect($this->chair->fresh()->via_dutiable_id)->toBeNull()
        ->and($this->chair->fresh()->end_date)->toBeNull()
        ->and($this->deanery->fresh()->via_dutiable_id)->toBeNull()
        ->and($this->deanery->fresh()->start_date->toDateString())->toBe('2026-07-01');
});

test('does nothing to a term that was already repaired by hand', function (): void {
    $this->chair->update(['via_dutiable_id' => null, 'end_date' => '2027-06-30']);

    runChairRepair();

    expect($this->chair->fresh()->end_date->toDateString())->toBe('2027-06-30')
        ->and($this->deanery->fresh()->via_dutiable_id)->toBeNull();
});

test('skips cleanly when the rows do not exist', function (): void {
    $this->council->delete();
    $this->chair->delete();
    $this->deanery->delete();

    runChairRepair();

    expect(Dutiable::query()->whereKey([REPAIR_CHAIR_ROW, REPAIR_DEANERY_ROW])->exists())->toBeFalse();
});
