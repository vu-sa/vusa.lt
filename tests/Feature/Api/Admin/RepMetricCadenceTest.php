<?php

use App\Models\Cadence;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Vote;
use App\Services\RepMetricReportService;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
use App\Settings\RepMetricSettings;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-20 10:00:00');
    Cadence::query()->delete();
    $this->previous = Cadence::factory()->forYear(2025)->create();
    $this->current = Cadence::factory()->forYear(2026)->create();
    $this->tenant = Tenant::query()->representational()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->reports = app(RepMetricReportService::class);
});

function cadenceMeeting(Institution $institution, string $date, string $recorded): Meeting
{
    return Meeting::factory()->hasAttached($institution)->create(['start_time' => $date, 'created_at' => $recorded]);
}

test('compares the current global term so far with the entire previous term', function (): void {
    Cadence::factory()->for($this->institution)->create(['start_date' => '2026-09-01', 'end_date' => '2027-08-31']);
    cadenceMeeting($this->institution, '2025-07-01 00:00:00', '2025-07-01');
    cadenceMeeting($this->institution, '2026-06-30 23:59:59', '2026-08-01');
    cadenceMeeting($this->institution, '2026-07-01 00:00:00', '2026-07-01');
    cadenceMeeting($this->institution, '2026-10-20 11:00:00', '2026-10-01');
    cadenceMeeting($this->institution, '2025-06-30 23:59:59', '2025-06-30');

    $report = $this->reports->report([$this->tenant->id]);
    expect($report['currentPeriod'])->toMatchArray(['cadenceId' => $this->current->id, 'from' => '2026-07-01', 'until' => '2026-10-20'])
        ->and($report['baselinePeriod'])->toMatchArray(['cadenceId' => $this->previous->id, 'from' => '2025-07-01', 'until' => '2026-06-30'])
        ->and($report['metrics'][0])->toMatchArray(['now' => 100.0, 'part' => 1, 'whole' => 1, 'baseline' => 50.0, 'baselinePart' => 1, 'baselineWhole' => 2])
        ->and(array_column($report['metrics'], 'key'))->toBe(['recorded_within_week', 'agenda_completion', 'task_completion']);
});

test('advance entries and exactly seven days count as timely but a second later does not', function (): void {
    cadenceMeeting($this->institution, '2026-10-01 10:00:00', '2026-09-30 10:00:00');
    cadenceMeeting($this->institution, '2026-10-02 10:00:00', '2026-10-09 10:00:00');
    cadenceMeeting($this->institution, '2026-10-03 10:00:00', '2026-10-10 10:00:01');
    expect($this->reports->report([$this->tenant->id])['metrics'][0])->toMatchArray(['now' => 66.7, 'part' => 2, 'whole' => 3]);
});

test('late voting information and task completions update retrospective figures', function (): void {
    $meeting = cadenceMeeting($this->institution, '2025-08-01', '2025-08-01');
    $item = AgendaItem::factory()->voting()->create(['meeting_id' => $meeting->id, 'created_at' => '2026-10-19']);
    Vote::factory()->create(['agenda_item_id' => $item->id, 'decision' => 'positive', 'student_vote' => 'positive', 'student_benefit' => 'positive', 'created_at' => '2026-10-19', 'updated_at' => now()]);
    Task::factory()->for($this->institution, 'taskable')->create(['created_at' => '2025-09-01', 'completed_at' => now()->subDay()]);
    Task::factory()->for($this->institution, 'taskable')->create(['created_at' => '2025-09-01', 'completed_at' => now()->addDay()]);
    $metrics = collect($this->reports->report([$this->tenant->id])['metrics'])->keyBy('key');
    expect($metrics['agenda_completion'])->toMatchArray(['baseline' => 100.0, 'baselinePart' => 1, 'baselineWhole' => 1])
        ->and($metrics['task_completion'])->toMatchArray(['baseline' => 50.0, 'baselinePart' => 1, 'baselineWhole' => 2]);
});

test('shared meetings and tasks count once and unrelated tenants are excluded', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    $second = Institution::factory()->for($other)->create();
    $meeting = cadenceMeeting($this->institution, '2026-08-01', '2026-08-02');
    $meeting->institutions()->attach($second);
    Task::factory()->for($meeting, 'taskable')->create(['created_at' => '2026-08-01', 'completed_at' => '2026-08-02']);
    $metrics = $this->reports->report([$this->tenant->id, $other->id])['metrics'];
    expect($metrics[0]['whole'])->toBe(1)->and($metrics[2]['whole'])->toBe(1);
    $third = Tenant::factory()->create(['type' => 'padalinys']);
    expect($this->reports->report([$third->id])['metrics'][0])->toMatchArray(['now' => null, 'whole' => 0]);
});

test('excluded and deleted institutions do not contribute to either period', function (): void {
    cadenceMeeting($this->institution, '2026-08-01', '2026-08-01');
    $type = InstitutionType::factory()->create();
    $this->institution->types()->attach($type);
    $settings = app(MeetingSettings::class);
    $settings->excluded_institution_type_ids = [$type->id];
    $settings->save();
    expect($this->reports->report([$this->tenant->id])['metrics'][0]['whole'])->toBe(0);
    $settings->excluded_institution_type_ids = [];
    $settings->save();
    $this->institution->delete();
    expect($this->reports->report([$this->tenant->id])['metrics'][0]['whole'])->toBe(0);
});

test('missing and deleted cadencies do not silently select another baseline', function (): void {
    $settings = app(RepMetricSettings::class);
    $settings->baseline_cadence_id = $this->previous->id;
    $settings->save();
    $this->previous->delete();
    Cadence::factory()->forYear(2024)->create();
    expect($this->reports->report()['baselinePeriod'])->toBeNull();
    $this->current->delete();
    expect($this->reports->report()['currentPeriod'])->toBeNull()
        ->and($this->reports->report()['metrics'][0]['now'])->toBeNull();
});

test('settings permissions control baseline selection without a roles permission', function (): void {
    $manager = makeUser($this->tenant);
    $manager->givePermissionTo(['settings.read.*', 'settings.update.*']);
    $root = InstitutionType::query()->firstOrFail();
    $representation = app(AtstovavimasSettings::class);
    $representation->student_rep_root_type_id = $root->id;
    $representation->save();
    $older = Cadence::factory()->forYear(2024)->create();
    asUser($manager)->post(route('settings.atstovavimas.update'), ['baseline_cadence_id' => $older->id])->assertRedirect();
    expect(app(AtstovavimasSettings::class)->student_rep_root_type_id)->toBe($root->id)
        ->and(app(RepMetricSettings::class)->baseline_cadence_id)->toBe($older->id)
        ->and($this->reports->report()['baselinePeriod']['cadenceId'])->toBe($older->id);
    asUser($manager)->post(route('settings.atstovavimas.update'), ['baseline_cadence_id' => null])->assertRedirect();
    expect($this->reports->report()['baselinePeriod']['cadenceId'])->toBe($this->previous->id);

    $reader = makeUser($this->tenant);
    $reader->givePermissionTo('settings.read.*');
    asUser($reader)->get(route('settings.atstovavimas.edit'))->assertOk();
    asUser($reader)->post(route('settings.atstovavimas.update'), ['baseline_cadence_id' => $older->id])->assertForbidden();
});

test('settings reject current future institution-specific and unknown cadencies', function (): void {
    $manager = makeAdminUser($this->tenant);
    $future = Cadence::factory()->forYear(2027)->create();
    $override = Cadence::factory()->forYear(2025)->for($this->institution)->create();
    foreach ([$this->current->id, $future->id, $override->id, 'unknown'] as $id) {
        asUser($manager)->post(route('settings.atstovavimas.update'), ['baseline_cadence_id' => $id])->assertSessionHasErrors('baseline_cadence_id');
    }
});

test('a cadency setting change invalidates a warmed tenant report', function (): void {
    $manager = makeAdminUser($this->tenant);
    $url = route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$this->tenant->id]]);
    asUser($manager)->getJson($url)->assertJsonPath('data.baselinePeriod.cadenceId', $this->previous->id);
    $older = Cadence::factory()->forYear(2024)->create();
    asUser($manager)->post(route('settings.atstovavimas.update'), ['baseline_cadence_id' => $older->id])->assertRedirect();
    asUser($manager)->getJson($url)->assertJsonPath('data.baselinePeriod.cadenceId', $older->id)
        ->assertJsonMissingPath('data.supplementary')->assertJsonMissingPath('data.metrics.0.target')->assertJsonMissingPath('data.metrics.0.trend');
});

test('the retired standalone metrics page is gone', function (): void {
    asUser(makeAdminUser($this->tenant))->get('/mano/rep-metrics')->assertNotFound();
});

test('agenda completion includes non-voting items and requires the main vote for voting items', function (): void {
    $meeting = cadenceMeeting($this->institution, '2026-08-01', '2026-08-01');
    AgendaItem::factory()->informational()->create(['meeting_id' => $meeting->id]);
    AgendaItem::factory()->deferred()->create(['meeting_id' => $meeting->id]);
    AgendaItem::factory()->break()->create(['meeting_id' => $meeting->id]);
    AgendaItem::factory()->create(['meeting_id' => $meeting->id]);
    $voting = AgendaItem::factory()->voting()->create(['meeting_id' => $meeting->id]);
    Vote::factory()->create(['agenda_item_id' => $voting->id, 'is_main' => false, 'decision' => 'positive', 'student_vote' => 'positive', 'student_benefit' => 'positive']);
    $metrics = collect($this->reports->report([$this->tenant->id])['metrics'])->keyBy('key');
    expect($metrics['agenda_completion'])->toMatchArray(['now' => 60.0, 'part' => 3, 'whole' => 5]);
    Vote::factory()->create(['agenda_item_id' => $voting->id, 'is_main' => true, 'decision' => 'positive', 'student_vote' => 'positive', 'student_benefit' => 'positive']);
    $metrics = collect($this->reports->report([$this->tenant->id])['metrics'])->keyBy('key');
    expect($metrics['agenda_completion'])->toMatchArray(['now' => 80.0, 'part' => 4, 'whole' => 5]);
});

test('reservation approval pickup and return tasks are excluded from both term totals', function (): void {
    $reservation = Reservation::factory()->create();
    foreach (['2025-08-01', '2026-08-01'] as $date) {
        Task::factory()->for($this->institution, 'taskable')->create(['created_at' => $date, 'completed_at' => $date]);
        Task::factory()->for($this->institution, 'taskable')->create(['created_at' => $date, 'completed_at' => null]);
        foreach ([ActionType::Approval, ActionType::Pickup, ActionType::Return] as $type) {
            Task::factory()->for($reservation, 'taskable')->create(['action_type' => $type, 'created_at' => $date, 'completed_at' => $date]);
        }
    }
    $metrics = collect($this->reports->report([$this->tenant->id])['metrics'])->keyBy('key');
    expect($metrics['task_completion'])->toMatchArray(['now' => 50.0, 'part' => 1, 'whole' => 2, 'baseline' => 50.0, 'baselinePart' => 1, 'baselineWhole' => 2]);
});

test('reports the current term as it stood 14 days ago, and nothing while the term is younger', function (): void {
    cadenceMeeting($this->institution, '2026-09-01 10:00:00', '2026-09-01 09:00:00');
    cadenceMeeting($this->institution, '2026-10-10 10:00:00', '2026-10-18 10:00:00');

    $report = $this->reports->report([$this->tenant->id]);
    expect($report['trendSince'])->toBe('2026-10-06')
        ->and($report['metrics'][0])->toMatchArray(['now' => 50.0, 'previous' => 100.0, 'previousPart' => 1, 'previousWhole' => 1]);

    $this->travelTo('2026-07-10 10:00:00');
    $young = $this->reports->report([$this->tenant->id]);
    expect($young['trendSince'])->toBeNull()->and($young['metrics'][0]['previous'])->toBeNull();
});
