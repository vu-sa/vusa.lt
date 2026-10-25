<?php

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionStatusSnapshot;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionStatusReplayService;
use App\Services\InstitutionStatusSnapshotService;
use App\Settings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\Core\LegacyAcademicCalendarService;
use Tests\Feature\Admin\Core\LegacyInstitutionStatusSnapshotService;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-20 10:00:00');
});

function legacyStatusSnapshots(): LegacyInstitutionStatusSnapshotService
{
    $calendar = new LegacyAcademicCalendarService;

    return new LegacyInstitutionStatusSnapshotService(new InstitutionStatusReplayService(
        new InstitutionActivityStatusService($calendar), $calendar, app(MeetingSettings::class),
    ));
}

function capturedStatusRows(): array
{
    return InstitutionStatusSnapshot::query()->orderBy('taken_on')->orderBy('tenant_id')->get()
        ->map(fn ($row) => $row->attributesToArray())
        ->map(fn ($row) => array_diff_key($row, ['id' => true]))->all();
}

test('capture preserves the legacy rows counts flags timestamps and progress', function (bool $repair): void {
    $tenants = Tenant::query()->representational()->limit(2)->get();
    $institutions = collect();
    foreach ([3, 10, 30, 90, 180] as $index => $periodicity) {
        $institutions->push(Institution::factory()->for($tenants[$index % 2])->create(['meeting_periodicity_days' => $periodicity]));
    }
    foreach ([
        ['2025-12-20 12:00:00', '2025-12-19 09:00:00', null],
        ['2026-10-02 12:00:00', '2026-10-05 09:00:00', null],
        ['2026-10-09 12:00:00', '2026-10-01 09:00:00', '2026-10-10 00:00:00'],
        ['2026-11-02 12:00:00', '2026-10-08 09:00:00', null],
        ['2026-10-12 23:59:59', '2026-10-12 23:59:59', null],
    ] as $index => [$start, $registered, $deleted]) {
        $meeting = Meeting::factory()->create(['start_time' => $start, 'created_at' => $registered, 'deleted_at' => $deleted]);
        $meeting->institutions()->attach([$institutions[$index]->id, $institutions[($index + 2) % 5]->id]);
    }
    foreach ($institutions as $institution) {
        InstitutionCheckIn::factory()->for($institution)->create(['start_date' => '2026-09-25', 'end_date' => '2026-10-03', 'created_at' => '2026-09-25 09:00:00']);
        InstitutionCheckIn::factory()->for($institution)->create(['start_date' => '2026-10-12', 'end_date' => '2026-10-22', 'created_at' => '2026-10-13 09:00:00']);
    }
    // Complete and partial days deliberately disagree with replay and separate the missing days.
    foreach (['2026-09-19', '2026-09-23', '2026-09-26'] as $day) {
        foreach (Tenant::query()->representational()->get() as $tenant) {
            InstitutionStatusSnapshot::factory()->for($tenant)->create([
                'taken_on' => $day,
                'institution_statuses' => [$institutions[0]->id => InstitutionActivityStatus::Healthy->value],
                'created_at' => '2026-09-10 12:00:00', 'updated_at' => '2026-09-10 12:00:00',
            ]);
        }
    }
    InstitutionStatusSnapshot::factory()->for($tenants[0])->create(['taken_on' => '2026-09-24', 'is_reconstructed' => true]);
    InstitutionStatusSnapshot::factory()->for($tenants[1])->create(['taken_on' => '2026-09-25', 'institution_statuses' => null]);
    $from = CarbonImmutable::parse('2026-09-20');
    $to = CarbonImmutable::today();
    $results = [];
    foreach ([legacyStatusSnapshots(), app(InstitutionStatusSnapshotService::class)] as $service) {
        $this->travelTo('2026-10-20 10:00:00');
        DB::beginTransaction();
        try {
            Cache::flush();
            $progress = 0;
            $written = $service->capture($from, $to, reconstructed: false, repair: $repair, progress: function () use (&$progress): void {
                $progress++;
            });
            $first = capturedStatusRows();
            $this->travel(5)->minutes();
            $repeat = $service->capture($from, $to, reconstructed: false, repair: $repair);
            $results[] = [$written, $progress, $first, $repeat, capturedStatusRows()];
        } finally {
            DB::rollBack();
        }
    }

    expect($results[1])->toBe($results[0]);
})->with([false, true]);

test('capture resumes after a failed progress callback without rewriting committed days', function (): void {
    $service = app(InstitutionStatusSnapshotService::class);
    $from = CarbonImmutable::parse('2026-10-16');
    $progress = 0;
    expect(fn () => $service->capture($from, now(), false, progress: function () use (&$progress): void {
        if (++$progress === 2) {
            throw new RuntimeException('Interrupted');
        }
    }))->toThrow(RuntimeException::class, 'Interrupted');
    $committed = capturedStatusRows();

    expect(collect($committed)->pluck('taken_on')->unique()->values()->all())->toBe(['2026-10-16', '2026-10-17']);
    $service->capture($from, now(), false);
    expect(array_slice(capturedStatusRows(), 0, count($committed)))->toBe($committed);
});

test('a failed bulk write rolls back only its day', function (): void {
    $writes = 0;
    $interrupt = true;
    DB::listen(function ($query) use (&$writes, &$interrupt): void {
        if ($interrupt && str_starts_with($query->sql, 'insert') && str_contains($query->sql, 'institution_status_snapshots') && ++$writes === 2) {
            throw new RuntimeException('Write interrupted');
        }
    });
    $service = app(InstitutionStatusSnapshotService::class);
    expect(fn () => $service->capture(now()->subDays(3), now(), false))->toThrow(RuntimeException::class, 'Write interrupted');
    expect(InstitutionStatusSnapshot::query()->pluck('taken_on')->map->toDateString()->unique()->values()->all())->toBe(['2026-10-17']);

    $interrupt = false;
    $service->capture(now()->subDays(3), now(), false);
    expect(InstitutionStatusSnapshot::query()->distinct()->count('taken_on'))->toBe(3);
});

test('snapshot reads are constant and writes are batched by day', function (): void {
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        if (str_contains($query->sql, 'institution_status_snapshots')) {
            $queries[] = $query->sql;
        }
    });

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(3), now(), false);

    expect(collect($queries)->filter(fn ($sql) => str_starts_with($sql, 'select')))->toHaveCount(1)
        ->and(collect($queries)->filter(fn ($sql) => str_starts_with($sql, 'insert')))->toHaveCount(3);
});

test('snapshot benchmarks compare identical backfill nightly rerun and repair workloads', function (): void {
    $tenants = Tenant::query()->representational()->get();
    for ($index = 0; $index < 100; $index++) {
        $institution = Institution::factory()->for($tenants[$index % $tenants->count()])->create(['meeting_periodicity_days' => 30]);
        $meeting = Meeting::factory()->create(['start_time' => '2026-01-15 12:00:00', 'created_at' => '2026-01-14 09:00:00']);
        $meeting->institutions()->attach($institution->id);
    }
    $results = [];
    foreach (['legacy' => legacyStatusSnapshots(), 'optimized' => app(InstitutionStatusSnapshotService::class)] as $name => $service) {
        DB::beginTransaction();
        try {
            foreach (['backfill', 'rerun', 'nightly', 'repair'] as $scenario) {
                if ($scenario === 'nightly') {
                    InstitutionStatusSnapshot::query()->whereDate('taken_on', today()->subDay())->delete();
                } elseif ($scenario === 'repair') {
                    InstitutionStatusSnapshot::query()->where('is_reconstructed', true)->update(['all' => 999]);
                }
                DB::flushQueryLog();
                DB::enableQueryLog();
                memory_reset_peak_usage();
                $memory = memory_get_usage();
                $start = hrtime(true);
                $written = $service->capture(now()->subDays(180), now()->subDay(), reconstructed: $scenario === 'backfill', repair: $scenario === 'repair');
                $elapsed = (hrtime(true) - $start) / 1_000_000;
                $queries = count(DB::getQueryLog());
                DB::disableQueryLog();
                $results[$name][$scenario] = [$written, capturedStatusRows()];
                fwrite(STDOUT, json_encode(['implementation' => $name, 'scenario' => $scenario, 'ms' => round($elapsed, 1), 'queries' => $queries, 'peak_delta_mb' => round((memory_get_peak_usage() - $memory) / 1024 / 1024, 1)]).PHP_EOL);
            }
        } finally {
            DB::rollBack();
        }
    }
    expect($results['optimized'])->toBe($results['legacy']);
})->skip(fn () => ! getenv('RUN_PERF_BENCHMARKS'), 'Run explicitly with RUN_PERF_BENCHMARKS=1');
