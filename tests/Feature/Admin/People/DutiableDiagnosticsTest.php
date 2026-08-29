<?php

use App\Actions\Dutiables\AnalyzeDutiableTimeline;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create(['places_to_occupy' => 1]);
    $this->holder = User::factory()->create();

    $this->cadence = Cadence::factory()->create([
        'institution_id' => null,
        'start_date' => '2024-07-01',
        'end_date' => '2025-06-30',
    ]);
});

function analyze(array $overrides = []): array
{
    $rows = Dutiable::query()->without('study_program')->with('duty')->get();

    return AnalyzeDutiableTimeline::execute($rows, Cadence::query()->get(), $overrides);
}

function codes(array $findings): array
{
    return collect($findings)->pluck('code')->unique()->sort()->values()->all();
}

function makeRow(array $attributes = []): Dutiable
{
    return Dutiable::factory()->create([
        'duty_id' => test()->duty->id,
        'dutiable_id' => test()->holder->id,
        'start_date' => '2024-07-01',
        'end_date' => '2025-06-30',
        ...$attributes,
    ]);
}

test('a clean, cadence-aligned, currently-filled row reports nothing', function (): void {
    $cadence = Cadence::factory()->create([
        'institution_id' => null,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    makeRow([
        'start_date' => $cadence->start_date->toDateString(),
        'end_date' => $cadence->end_date->toDateString(),
    ]);

    // The stale 2024–2025 ladder row would otherwise be the nearest cadence and make this
    // look like drift; deleting it keeps the fixture about one term only.
    $this->cadence->delete();

    expect(codes(analyze()))->toBeEmpty();
});

test('an end before the start is an error', function (): void {
    makeRow(['start_date' => '2025-06-30', 'end_date' => '2024-07-01']);

    expect(codes(analyze()))->toContain('inverted');
});

test('two intersecting stints on the same duty are an overlap, with the fix date computed', function (): void {
    makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30']);
    makeRow(['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);

    $overlap = collect(analyze())->firstWhere('code', 'overlap');

    expect($overlap)->not->toBeNull()
        ->and($overlap['detail']['suggested_end'])->toBe('2024-12-31');
});

test('two genuinely separate stints are not an overlap', function (): void {
    makeRow(['start_date' => '2020-07-01', 'end_date' => '2021-06-30']);
    makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30']);

    expect(codes(analyze()))->not->toContain('overlap');
});

test('one term ending the day the next begins is flagged as a shared boundary', function (): void {
    makeRow(['start_date' => '2023-07-01', 'end_date' => '2024-07-01']);
    makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30']);

    $finding = collect(analyze())->firstWhere('code', 'boundary_shared');

    expect($finding)->not->toBeNull()
        ->and($finding['detail']['suggested_end'])->toBe('2024-06-30')
        ->and($finding['severity'])->toBe('warning');
});

test('an owning-tenant row and a cross-tenant rep row for the same person never collide', function (): void {
    $other = Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? Tenant::factory()->create();

    makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30']);
    makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30', 'tenant_id' => $other->id]);

    expect(codes(analyze()))->not->toContain('overlap');
});

test('an open-ended row whose term is long over is stale, and carries the date that closes it', function (): void {
    makeRow(['start_date' => '2024-07-01', 'end_date' => null]);

    $finding = collect(analyze())->firstWhere('code', 'open_ended_stale');

    expect($finding)->not->toBeNull()
        ->and($finding['detail']['suggested_end'])->toBe('2025-06-30');
});

test('an open-ended row inside the current term is left alone', function (): void {
    $year = now()->year;
    Cadence::factory()->create([
        'institution_id' => null,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);

    makeRow(['start_date' => now()->subWeek()->toDateString(), 'end_date' => null]);

    expect(codes(analyze()))->not->toContain('open_ended_stale');
});

test('a derived row whose dates drifted from its source is flagged', function (): void {
    $source = makeRow();
    makeRow(['via_dutiable_id' => $source->id, 'start_date' => '2024-09-01', 'end_date' => '2025-06-30']);

    expect(codes(analyze()))->toContain('ex_officio_drift');
});

test('a derived row that still mirrors its source is not', function (): void {
    $source = makeRow();
    makeRow(['via_dutiable_id' => $source->id]);

    expect(codes(analyze()))->not->toContain('ex_officio_drift');
});

test('a start a few weeks off the cadence is informational drift', function (): void {
    makeRow(['start_date' => '2024-06-01', 'end_date' => '2025-06-30']);

    $finding = collect(analyze())->firstWhere('code', 'off_cadence');

    expect($finding)->not->toBeNull()
        ->and($finding['severity'])->toBe('info')
        ->and($finding['detail']['drift_days'])->toHaveKey('start');
});

test('a start far outside the cadence is deliberate, not drift', function (): void {
    makeRow(['start_date' => '2024-01-15', 'end_date' => '2025-06-30']);

    expect(codes(analyze()))->not->toContain('off_cadence');
});

test('an unfilled seat is reported against places_to_occupy', function (): void {
    $this->duty->update(['places_to_occupy' => 3]);
    makeRow(['start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonth()->toDateString()]);

    $finding = collect(analyze())->firstWhere('code', 'understaffed');

    expect($finding['detail'])->toBe(['active' => 1, 'places_to_occupy' => 3]);
});

test('projected state is analysed instead of stored state, which is what the diff delta needs', function (): void {
    $row = makeRow(['start_date' => '2024-06-01', 'end_date' => '2025-06-30']);

    expect(codes(analyze()))->toContain('off_cadence');

    $aligned = analyze([$row->id => ['start_date' => '2024-07-01', 'end_date' => '2025-06-30']]);

    expect(codes($aligned))->not->toContain('off_cadence');
});

test('an institution cadence wins over the global ladder outright', function (): void {
    Cadence::factory()->create([
        'institution_id' => $this->institution->id,
        'start_date' => '2024-05-18',
        'end_date' => '2025-05-17',
    ]);

    // Aligned to the institution's own 05-18 boundary: correct here, drift under the global one.
    makeRow(['start_date' => '2024-05-18', 'end_date' => '2025-05-17']);

    expect(codes(analyze()))->not->toContain('off_cadence');
});

describe('rows covering more than one term', function (): void {
    beforeEach(function (): void {
        $this->second = Cadence::factory()->create([
            'institution_id' => null,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);
    });

    test('a two-term row is reported with both boundaries', function (): void {
        makeRow(['start_date' => '2024-07-01', 'end_date' => '2026-06-30']);

        $finding = collect(analyze())->firstWhere('code', 'spans_cadences');

        expect($finding)->not->toBeNull()
            ->and($finding['severity'])->toBe('info')
            ->and($finding['detail']['count'])->toBe(2)
            ->and($finding['detail']['suggested_start'])->toBe('2024-07-01')
            ->and($finding['detail']['suggested_end'])->toBe('2026-06-30');
    });

    test('a single-term row is not reported', function (): void {
        makeRow(['start_date' => '2024-07-01', 'end_date' => '2025-06-30']);

        expect(codes(analyze()))->not->toContain('spans_cadences');
    });

    /**
     * The bug this replaced: both edges were measured against the *start*'s term, so a
     * two-term row's end drifted by a full year and the 45-day clamp hid it entirely.
     */
    test('each edge drifts against its own term', function (): void {
        makeRow(['start_date' => '2024-07-05', 'end_date' => '2026-06-20']);

        $finding = collect(analyze())->firstWhere('code', 'off_cadence');

        expect($finding['detail']['drift_days'])->toBe(['start' => 4, 'end' => 10])
            ->and($finding['detail']['cadence_ids']['end'])->toBe($this->second->id);
    });
});
