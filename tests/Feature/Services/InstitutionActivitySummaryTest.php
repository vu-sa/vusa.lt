<?php

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('activity summaries preserve status and dates without loading meeting history', function (array $meetings, array $checkIns, string $status): void {
    $this->travelTo('2026-11-08 12:00:00');
    $tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($tenant)->create(['meeting_periodicity_days' => 30]);
    $other = Institution::factory()->for($tenant)->create();
    $user = User::factory()->create();

    foreach ($meetings as $date) {
        Meeting::factory()->hasAttached($institution)->create(['start_time' => $date]);
    }

    foreach ($checkIns as [$start, $end]) {
        InstitutionCheckIn::factory()->for($institution)->for($tenant)->for($user)->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    InstitutionCheckIn::factory()->for($other)->for($tenant)->for($user)->create([
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-07',
    ]);
    $service = app(InstitutionActivityStatusService::class);
    $summary = $service->withSummary(Institution::query()->whereKey($institution->id))->firstOrFail();

    $resolved = $service->resolve($summary);

    expect($resolved->status->value)->toBe($status)
        ->and($resolved->toArray())->toBe($service->resolve($institution->fresh())->toArray())
        ->and($summary->relationLoaded('meetings'))->toBeFalse();
})->with([
    'no activity' => [[], [], 'no_activity'],
    'overdue meeting' => [['2026-10-01 12:00:00'], [], 'overdue'],
    'next and last meetings' => [
        ['2026-10-01 12:00:00', '2026-11-08 12:00:00', '2026-11-09 12:00:00', '2026-11-10 12:00:00'],
        [],
        'covered_by_upcoming_meeting',
    ],
    'latest completed coverage, excluding future coverage' => [
        ['2026-10-01 12:00:00'],
        [['2026-09-01', '2026-09-10'], ['2026-11-01', '2026-11-05'], ['2026-11-20', '2026-11-30']],
        'recent_check_in',
    ],
    'active coverage and completed coverage' => [
        ['2026-10-01 12:00:00'],
        [['2026-09-01', '2026-09-10'], ['2026-11-01', '2026-11-05'], ['2026-11-08', '2026-11-10']],
        'covered_by_check_in',
    ],
]);
