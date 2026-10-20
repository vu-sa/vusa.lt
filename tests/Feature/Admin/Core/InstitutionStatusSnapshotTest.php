<?php

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionStatusSnapshot;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\InstitutionStatusSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-20 10:00:00');

    $this->tenant = Tenant::factory()->create(['type' => 'padalinys']);
    $this->institution = Institution::factory()->for($this->tenant)->create(['meeting_periodicity_days' => 10]);
});

function snapshotFor(Tenant $tenant, string $day): InstitutionStatusSnapshot
{
    return InstitutionStatusSnapshot::query()->where('tenant_id', $tenant->id)->whereDate('taken_on', $day)->firstOrFail();
}

test('a late-registered meeting is activity on the day it was entered, not a rewrite of the past', function (): void {
    // Held on 12 Oct, entered on 15 Oct: until the 15th the body looked like it had no activity.
    $meeting = Meeting::factory()->create(['start_time' => '2026-10-12 12:00:00', 'created_at' => '2026-10-15 09:00:00']);
    $meeting->institutions()->attach($this->institution->id);

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(12), now()->subDay(), reconstructed: true);

    $beforeEntry = snapshotFor($this->tenant, '2026-10-14');
    expect($beforeEntry->no_activity)->toBe(1)
        ->and($beforeEntry->current)->toBe(0)
        ->and($beforeEntry->is_reconstructed)->toBeTrue();

    $entryDay = snapshotFor($this->tenant, '2026-10-15');
    expect($entryDay->meetings_registered)->toBe(1)
        ->and($entryDay->left_attention)->toBe(1)
        ->and($entryDay->entered_attention)->toBe(0)
        ->and(snapshotFor($this->tenant, '2026-10-12')->meetings_held)->toBe(0)
        ->and(snapshotFor($this->tenant, '2026-10-15')->meetings_held)->toBe(0);
});

test('check-ins registered and meetings held are counted on their own day', function (): void {
    $meeting = Meeting::factory()->create(['start_time' => '2026-10-10 12:00:00', 'created_at' => '2026-10-09 09:00:00']);
    $meeting->institutions()->attach($this->institution->id);
    InstitutionCheckIn::factory()->for($this->institution)->create([
        'start_date' => '2026-10-12',
        'end_date' => '2026-10-14',
        'created_at' => '2026-10-12 08:00:00',
    ]);

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(12), now()->subDay(), reconstructed: false);

    expect(snapshotFor($this->tenant, '2026-10-10')->meetings_held)->toBe(1)
        ->and(snapshotFor($this->tenant, '2026-10-09')->meetings_registered)->toBe(1)
        ->and(snapshotFor($this->tenant, '2026-10-12')->check_ins_registered)->toBe(1);
});

test('capturing a range twice keeps one row per padalinys and day, and never stores today', function (): void {
    $service = app(InstitutionStatusSnapshotService::class);

    $service->capture(now()->subDays(3), now(), reconstructed: false);
    $service->capture(now()->subDays(3), now(), reconstructed: false);

    $rows = InstitutionStatusSnapshot::query()->where('tenant_id', $this->tenant->id)->get();

    expect($rows)->toHaveCount(3)
        ->and($rows->pluck('taken_on')->map->toDateString()->contains('2026-10-20'))->toBeFalse();
});

test('metrics:reps:snapshot closes yesterday, and --from rebuilds a range as reconstructed', function (): void {
    Artisan::call('metrics:reps:snapshot');

    expect(snapshotFor($this->tenant, '2026-10-19')->is_reconstructed)->toBeFalse();

    Artisan::call('metrics:reps:snapshot', ['--from' => '2026-10-15']);

    expect(InstitutionStatusSnapshot::query()->where('tenant_id', $this->tenant->id)->count())->toBe(180)
        ->and(snapshotFor($this->tenant, '2026-10-17')->is_reconstructed)->toBeTrue();
});
