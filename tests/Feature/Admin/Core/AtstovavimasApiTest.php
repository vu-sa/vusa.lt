<?php

use App\Enums\InstitutionActivityCampaign;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionLink;
use App\Models\InstitutionStatusSnapshot;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionStatusSnapshotService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = makeAdminUser(Tenant::query()->first());
    $this->tenant = Tenant::factory()->create(['type' => 'padalinys']);
    $this->institution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Tiesioginė institucija', 'en' => 'Direct institution'],
    ]);
});

test('timeline requires an authenticated user', function (): void {
    $this->getJson(route('api.v1.admin.visak.timeline', [
        'tenant_ids' => [$this->tenant->id],
    ]))->assertUnauthorized();
});

test('timeline rejects tenants outside the visible scope', function (): void {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertForbidden();
});

test('recently ended check-ins have their own summary bucket and indexed status', function (): void {
    $this->travelTo('2026-11-20 12:00:00');
    $this->institution->update(['meeting_periodicity_days' => 30]);
    InstitutionCheckIn::factory()->for($this->institution)->create(['start_date' => '2026-09-01', 'end_date' => '2026-11-17']);

    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline', ['tenant_ids' => [$this->tenant->id]]))
        ->assertOk()
        ->assertJsonPath('data.institutions.0.activity_status.status', 'recent_check_in')
        ->assertJsonPath('data.institution_summary.recent_check_in', 1)
        ->assertJsonPath('data.institution_summary.needs_attention', 0)
        ->assertJsonPath('data.institution_summary.current', 0)
        ->assertJsonPath('data.institution_summary.all', 1);

    expect($this->institution->fresh()->toSearchableArray()['activity_status'])->toBe('recent_check_in');
});

test('timeline returns direct institutions with summaries but no meetings or relations', function (): void {
    $relatedTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $relatedInstitution = Institution::factory()->for($relatedTenant)->create([
        'name' => ['lt' => 'Susijusi institucija', 'en' => 'Related institution'],
    ]);

    InstitutionLink::factory()->create(['source_institution_id' => $this->institution->id, 'target_institution_id' => $relatedInstitution->id]);

    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(10)]);
    $meeting->institutions()->attach($this->institution->id);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.institution_summary.all', 1)
        ->assertJsonPath('data.institutions.0.id', $this->institution->id);

    $data = $response->json('data');

    // Tenant timelines are direct-only: relationships are not resolved
    expect($data)->not->toHaveKey('related_institutions');

    // Meetings are served by the windowed meetings endpoint, not the timeline
    $institution = $data['institutions'][0];
    expect($institution)->not->toHaveKey('meetings')
        ->toHaveKeys(['activity_status', 'check_ins', 'duties', 'tenant']);

    // Summaries remain fully populated
    expect($data['institution_summary'])->toHaveKeys(['all', 'needs_attention', 'overdue', 'approaching', 'no_activity', 'current']);
    expect($data['representative_activity']['stats'])->toHaveKeys(['total', 'activeToday', 'activeLast7Days', 'activeLast30Days', 'neverLoggedIn']);
});

test('status history requires an authenticated user', function (): void {
    $this->getJson(route('api.v1.admin.visak.timeline.history', [
        'tenant_ids' => [$this->tenant->id],
    ]))->assertUnauthorized();
});

test('status history rejects tenants outside the visible scope', function (): void {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertForbidden();
});

test('status history validates the days parameter', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 3,
        ]))
        ->assertUnprocessable();

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 200,
        ]))
        ->assertUnprocessable();
});

test('status history defaults to 90 days when none is given', function (): void {
    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertSuccessful()
        ->assertJsonCount(90, 'data.points');
});

test('status history reflects day-to-day changes in institution status', function (): void {
    $this->institution->update(['meeting_periodicity_days' => 10]);

    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(90), 'created_at' => now()->subDays(90)]);
    $meeting->institutions()->attach($this->institution->id);

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 90,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(90, 'data.points');

    $series = $response->json('data.points');

    // Chronologically ascending, from 89 days ago through today
    expect($series[0]['date'])->toBe(now()->subDays(89)->toDateString())
        ->and($series[89]['date'])->toBe(now()->toDateString());

    // Shortly after the meeting the institution is still within its periodicity window
    $recoveredDay = collect($series)->firstWhere('date', now()->subDays(85)->toDateString());
    expect($recoveredDay['overdue'])->toBe(0)
        ->and($recoveredDay['current'])->toBe(1);

    // By today it has drifted well past its 10-day periodicity
    $today = collect($series)->last();
    expect($today['overdue'])->toBe(1)
        ->and($today['current'])->toBe(0);

    // Every day's buckets add up to the total institution count
    foreach ($series as $day) {
        expect($day['current'] + $day['approaching'] + $day['overdue'] + $day['no_activity'])->toBe($day['all']);
    }
});

test('status history counts institutions without meetings or check-ins as no_activity on every day, without resolving them per day', function (): void {
    $this->institution->update(['meeting_periodicity_days' => 10]);
    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(90), 'created_at' => now()->subDays(90)]);
    $meeting->institutions()->attach($this->institution->id);

    // Never met, no check-ins: the perf shortcut should still count it correctly
    // every single day, purely via the fixed idle count, not a per-day resolve().
    Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Neaktyvi institucija', 'en' => 'Idle institution'],
    ]);

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 90,
        ]))
        ->assertSuccessful()
        ->assertJsonCount(90, 'data.points');

    $series = $response->json('data.points');

    foreach ($series as $day) {
        expect($day['all'])->toBe(2)
            ->and($day['no_activity'])->toBeGreaterThanOrEqual(1)
            ->and($day['needs_attention'])->toBeGreaterThanOrEqual(1)
            ->and($day['current'] + $day['approaching'] + $day['overdue'] + $day['no_activity'])->toBe($day['all']);
    }

    // By today the met institution has drifted into overdue, the idle one is still no_activity
    $today = collect($series)->last();
    expect($today['overdue'])->toBe(1)
        ->and($today['no_activity'])->toBe(1)
        ->and($today['needs_attention'])->toBe(2);
});

test('status history keeps closed days and invalidates live counts after a mutation', function (): void {
    $params = [
        'tenant_ids' => [$this->tenant->id],
        'days' => 30,
    ];

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(29), now()->subDay(), reconstructed: true);

    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', $params))
        ->assertSuccessful()
        ->assertJsonPath('data.points.29.all', 1);

    // A newly created institution is not reflected without a refresh...
    Institution::factory()->for($this->tenant)->create();

    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', $params))
        ->assertSuccessful()
        ->assertJsonPath('data.points.29.all', 2);

    // ...but is picked up once the cache is bypassed
    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', [...$params, 'refresh' => 1]))
        ->assertSuccessful()
        ->assertJsonPath('data.points.29.all', 2);
});

test('status history sweep matches resolve() day-by-day, including meeting/check-in overtaking and upcoming-meeting transitions', function (): void {
    // Everything is registered long before the window, so what was known equals what exists now.
    // A: only a meeting, initially in the future relative to older days in the
    // range, then in the past — exercises the meeting cursor crossing its
    // "upcoming" boundary partway through the sweep.
    $institutionA = $this->institution;
    $institutionA->update(['meeting_periodicity_days' => 15]);
    $meetingA = Meeting::factory()->create(['start_time' => now()->subDays(40), 'created_at' => now()->subDays(100)]);
    $meetingA->institutions()->attach($institutionA->id);

    // B: only a check-in — not yet started, then covering, then completed and aging.
    $institutionB = Institution::factory()->for($this->tenant)->create([
        'meeting_periodicity_days' => 20,
    ]);
    InstitutionCheckIn::factory()->for($institutionB)->create([
        'start_date' => now()->subDays(55)->toDateString(),
        'created_at' => now()->subDays(100),
        'end_date' => now()->subDays(40)->toDateString(),
    ]);

    // C: a meeting, later overtaken by a check-in as the "latest activity" —
    // exercises the checkInIsLatestActivity comparison against a real meeting.
    $institutionC = Institution::factory()->for($this->tenant)->create([
        'meeting_periodicity_days' => 10,
    ]);
    $meetingC = Meeting::factory()->create(['start_time' => now()->subDays(50), 'created_at' => now()->subDays(100)]);
    $meetingC->institutions()->attach($institutionC->id);
    InstitutionCheckIn::factory()->for($institutionC)->create([
        'start_date' => now()->subDays(20)->toDateString(),
        'created_at' => now()->subDays(20),
        'end_date' => now()->subDays(10)->toDateString(),
    ]);

    $institutions = collect([$institutionA, $institutionB, $institutionC]);
    $days = 60;

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => $days,
        ]))
        ->assertSuccessful()
        ->assertJsonCount($days, 'data.points');

    $series = $response->json('data.points');

    // Ground truth: call resolve() directly for every institution and every date,
    // completely independent of the sweep implementation being tested.
    $activityStatusService = app(InstitutionActivityStatusService::class);
    $today = now()->startOfDay();

    for ($offset = $days - 1; $offset >= 0; $offset--) {
        $date = $today->copy()->subDays($offset)->endOfDay();
        $dayIndex = $days - 1 - $offset;

        $expected = ['overdue' => 0, 'approaching' => 0, 'recent_check_in' => 0, 'no_activity' => 0, 'needs_attention' => 0];
        foreach ($institutions as $institution) {
            $status = $activityStatusService->resolve($institution->fresh(), $date)->status;
            if ($status->value === 'overdue') {
                $expected['overdue']++;
            }
            if ($status->value === 'approaching') {
                $expected['approaching']++;
            }
            if ($status->value === 'no_activity') {
                $expected['no_activity']++;
            }
            if ($status->value === 'recent_check_in') {
                $expected['recent_check_in']++;
            }
            if ($status->requiresAction()) {
                $expected['needs_attention']++;
            }
        }

        expect($series[$dayIndex])->toMatchArray([
            'date' => $date->toDateString(),
            'all' => 3,
            'overdue' => $expected['overdue'],
            'approaching' => $expected['approaching'],
            'no_activity' => $expected['no_activity'],
            'needs_attention' => $expected['needs_attention'],
            'recent_check_in' => $expected['recent_check_in'],
            'current' => 3 - $expected['needs_attention'] - $expected['recent_check_in'],
        ]);
    }
});

test('status history only counts a meeting from the day it was registered', function (): void {
    $this->institution->update(['meeting_periodicity_days' => 10]);

    // Held 20 days ago but only entered 5 days ago: the gap before that was real.
    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(20), 'created_at' => now()->subDays(5)]);
    $meeting->institutions()->attach($this->institution->id);

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    $series = collect(asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', ['tenant_ids' => [$this->tenant->id], 'days' => 30]))
        ->assertSuccessful()
        ->json('data.points'))->keyBy('date');

    expect($series[now()->subDays(10)->toDateString()]['no_activity'])->toBe(1)
        ->and($series[now()->subDays(4)->toDateString()]['no_activity'])->toBe(0)
        ->and($series[now()->subDays(4)->toDateString()]['overdue'])->toBe(1);
});

test('status history keeps a later-deleted meeting for the days before it was deleted', function (): void {
    $this->institution->update(['meeting_periodicity_days' => 10]);

    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(20), 'created_at' => now()->subDays(20)]);
    $meeting->institutions()->attach($this->institution->id);
    $meeting->forceFill(['deleted_at' => now()->subDays(3)])->saveQuietly();

    app(InstitutionStatusSnapshotService::class)->capture(now()->subDays(89), now()->subDay(), reconstructed: true);

    $series = collect(asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', ['tenant_ids' => [$this->tenant->id], 'days' => 30]))
        ->assertSuccessful()
        ->json('data.points'))->keyBy('date');

    expect($series[now()->subDays(7)->toDateString()]['overdue'])->toBe(1)
        ->and($series[now()->subDays(18)->toDateString()]['current'])->toBe(1)
        ->and($series[now()->toDateString()]['no_activity'])->toBe(1);
});

test('status history serves stored days summed over padaliniai and replays only today', function (): void {
    $other = Tenant::factory()->create(['type' => 'padalinys']);
    $yesterday = now()->subDay()->toDateString();

    InstitutionStatusSnapshot::factory()->for($this->tenant)->create(['taken_on' => $yesterday, 'all' => 4, 'overdue' => 2, 'meetings_registered' => 3]);
    InstitutionStatusSnapshot::factory()->for($other)->create(['taken_on' => $yesterday, 'all' => 6, 'overdue' => 1, 'meetings_registered' => 1, 'is_reconstructed' => true]);

    $series = collect(asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', ['tenant_ids' => [$this->tenant->id, $other->id], 'days' => 30]))
        ->assertSuccessful()
        ->json('data.points'))->keyBy('date');

    expect($series[$yesterday])->toMatchArray(['all' => 10, 'overdue' => 3, 'meetings_registered' => 4, 'reconstructed' => true])
        ->and($series[now()->toDateString()]['all'])->toBe(1);
});

test('rep metrics are measured per padalinys across global cadencies', function (): void {
    Cadence::query()->globalLadder()->delete();
    Cadence::factory()->create(['start_date' => now()->subYear()->toDateString(), 'end_date' => now()->addYear()->toDateString()]);
    $other = Tenant::factory()->create(['type' => 'padalinys']);
    $otherInstitution = Institution::factory()->for($other)->create();

    $recorded = Meeting::factory()->create(['start_time' => now()->subDays(10), 'created_at' => now()->subDays(9)]);
    $recorded->institutions()->attach($this->institution->id);
    $late = Meeting::factory()->create(['start_time' => now()->subDays(20), 'created_at' => now()->subDays(2)]);
    $late->institutions()->attach($otherInstitution->id);

    $metrics = fn (Tenant $tenant) => collect(asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$tenant->id]]))
        ->assertSuccessful()
        ->json('data.metrics'))->keyBy('key')->get('recorded_within_week');

    expect($metrics($this->tenant))->toMatchArray(['now' => 100.0, 'part' => 1, 'whole' => 1])
        ->and($metrics($other))->toMatchArray(['now' => 0.0, 'part' => 0, 'whole' => 1]);
});

test('rep metrics follow the same access rules as the other padaliniai statistics', function (): void {
    $this->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$this->tenant->id]]))->assertUnauthorized();

    asUser(makeUser(Tenant::factory()->create(['type' => 'padalinys'])))
        ->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$this->tenant->id]]))
        ->assertForbidden();
});

test('meetings requires an authenticated user', function (): void {
    $this->getJson(route('api.v1.admin.visak.meetings', [
        'tenant_ids' => [$this->tenant->id],
        'from' => now()->subMonth()->toDateString(),
        'until' => now()->addMonth()->toDateString(),
    ]))->assertUnauthorized();
});

test('meetings in a padalinys the user does not manage leave out its non-public bodies', function (): void {
    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(5)]);
    $meeting->institutions()->attach($this->institution->id);
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('meetings validates the date window', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->toDateString(),
            'until' => now()->subDay()->toDateString(),
        ]))
        ->assertUnprocessable();

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subYears(3)->toDateString(),
            'until' => now()->toDateString(),
        ]))
        ->assertUnprocessable();
});

test('meetings returns only in-window meetings of the requested tenants, trimmed for the Gantt', function (): void {
    $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $otherInstitution = Institution::factory()->for($otherTenant)->create();

    $inWindow = Meeting::factory()->create(['title' => 'In window', 'start_time' => now()->subDays(5)]);
    $inWindow->institutions()->attach($this->institution->id);

    $outOfWindow = Meeting::factory()->create(['title' => 'Out of window', 'start_time' => now()->subMonths(4)]);
    $outOfWindow->institutions()->attach($this->institution->id);

    $otherTenantMeeting = Meeting::factory()->create(['title' => 'Other tenant', 'start_time' => now()->subDays(5)]);
    $otherTenantMeeting->institutions()->attach($otherInstitution->id);

    // Five agenda items with votes: payload must be trimmed to the first four
    $agendaItems = AgendaItem::factory()->count(5)->for($inWindow)->create();
    $agendaItems->each(fn (AgendaItem $item) => Vote::factory()->for($item)->create([
        'is_main' => true,
        'student_vote' => 'positive',
        'decision' => 'positive',
    ]));

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    $meeting = $response->json('data.0');
    expect($meeting)->toMatchArray(['id' => (string) $inWindow->id, 'institution_id' => (string) $this->institution->id, 'title' => 'In window'])
        ->toHaveKeys(['start_time', 'type_slug', 'completion_status', 'has_report', 'has_protocol', 'has_calendar_event', 'calendar_event_is_draft', 'agenda_items', 'agenda_items_count'])
        ->and($meeting['agenda_items_count'])->toBe(5)
        ->and($meeting['agenda_items'])->toHaveCount(4);

    // Agenda items carry only the main-vote fields the Gantt tooltip renders
    $item = $meeting['agenda_items'][0];
    expect($item)->toHaveKeys(['id', 'title', 'type', 'student_vote', 'decision'])->not->toHaveKey('student_benefit')->not->toHaveKey('votes')
        ->toMatchArray(['student_vote' => 'positive', 'decision' => 'positive']);
});

test('meetings are cached per window and refresh bypasses the cache', function (): void {
    $first = Meeting::factory()->create(['title' => 'First', 'start_time' => now()->subDays(5)]);
    $first->institutions()->attach($this->institution->id);

    $params = [
        'tenant_ids' => [$this->tenant->id],
        'from' => now()->subMonth()->toDateString(),
        'until' => now()->addMonth()->toDateString(),
    ];

    asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', $params))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    // A new meeting invalidates the cached window.
    $second = Meeting::factory()->create(['title' => 'Second', 'start_time' => now()->subDays(4)]);
    $second->institutions()->attach($this->institution->id);

    asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', $params))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', [...$params, 'refresh' => 1]))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

test('representatives are searched and paginated without loading the full list', function (): void {
    $duty = Duty::factory()->for($this->institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

    collect([
        ['name' => 'First Representative', 'email' => 'first-representative@example.test'],
        ['name' => 'Second Representative', 'email' => 'second-representative@example.test'],
        ['name' => 'Third Representative', 'email' => 'third-representative@example.test'],
    ])->each(function (array $attributes) use ($duty): void {
        $representative = User::factory()->create($attributes);
        $representative->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
    });

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.representatives', [
            'tenant_ids' => [$this->tenant->id],
            'per_page' => 2,
        ]))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.users')
        ->assertJsonPath('data.pagination.total', 3)
        ->assertJsonPath('data.pagination.last_page', 2);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.representatives', [
            'tenant_ids' => [$this->tenant->id],
            'search' => 'second-representative@example.test',
        ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.users')
        ->assertJsonPath('data.users.0.email', 'second-representative@example.test');
});

test('representative activity counts who acted today, this week, this month and never', function (): void {
    $duty = Duty::factory()->for($this->institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    collect([now(), now()->subDays(3), now()->subDays(20), now()->subDays(60), null])
        ->each(function ($lastAction) use ($duty): void {
            $representative = User::factory()->create();
            $representative->forceFill(['last_action' => $lastAction])->save();
            $representative->duties()->attach($duty, ['start_date' => now()->subYear(), 'end_date' => null]);
        });

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline', ['tenant_ids' => [$this->tenant->id]]))
        ->assertJsonCount(3, 'data.representative_activity.preview_users')
        ->assertJsonPath('data.representative_activity.preview_users.0.category', 'today')
        ->assertJsonPath('data.representative_activity.preview_users.1.category', 'week')
        ->assertJsonPath('data.representative_activity.preview_users.2.category', 'month')
        ->assertJsonPath('data.representative_activity.stats', [
            'total' => 5,
            'activeToday' => 1,
            'activeLast7Days' => 2,
            'activeLast30Days' => 3,
            'neverLoggedIn' => 1,
        ]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.representatives', ['tenant_ids' => [$this->tenant->id], 'category' => 'inactive']))
        ->assertJsonPath('data.pagination.total', 2);
});

/**
 * VU SA's own bodies do meet, and dropping them server-side left the chart quietly
 * incomplete. They are marked instead, so the chart can offer to hide them.
 */
describe('internal bodies', function (): void {
    beforeEach(function (): void {
        $this->internalType = InstitutionType::factory()->create([
            'extra_attributes' => ['governance_scope' => 'vusa'],
        ]);

        $settings = app(MeetingSettings::class);
        $settings->excluded_institution_type_ids = [$this->internalType->id];
        $settings->save();

        $this->internalInstitution = Institution::factory()->for($this->tenant)->create();
        $this->internalInstitution->types()->attach($this->internalType);
    });

    test('an excluded type no longer hides its meetings from the chart', function (): void {
        $meeting = Meeting::factory()->create(['title' => 'Valdybos posėdis', 'start_time' => now()->subDays(5)]);
        $meeting->institutions()->attach($this->internalInstitution->id);

        $titles = collect(
            asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', [
                'tenant_ids' => [$this->tenant->id],
                'from' => now()->subMonth()->toDateString(),
                'until' => now()->addMonth()->toDateString(),
            ]))->assertSuccessful()->json('data')
        )->pluck('title');

        expect($titles)->toContain('Valdybos posėdis');
    });

    test('the timeline marks which rows are internal so the chart can hide them', function (): void {
        $rows = collect(
            asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline', [
                'tenant_ids' => [$this->tenant->id],
            ]))->assertSuccessful()->json('data.institutions')
        )->keyBy('id');

        expect($rows->get((string) $this->internalInstitution->id)['is_internal'])->toBeTrue()
            ->and($rows->get((string) $this->institution->id)['is_internal'])->toBeFalse();
    });

    test('the timeline leaves an excluded type out of the attention list as well as the numbers', function (): void {
        $response = asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]));

        $rows = collect($response->json('data.institutions'))->keyBy('id');
        expect($rows->get((string) $this->internalInstitution->id)['in_summary'])->toBeFalse()
            ->and($rows->get((string) $this->institution->id)['in_summary'])->toBeTrue()
            ->and($response->json('data.institution_summary.all'))->toBe(1);
    });
});

/**
 * The padaliniai Gantt is open to every admin: public meetings anywhere, the user's own bodies,
 * and whole padaliniai only where they manage them.
 */
describe('padaliniai gantt', function (): void {
    beforeEach(function (): void {
        $this->publicType = InstitutionType::factory()->create([]);

        $settings = app(MeetingSettings::class);
        $settings->public_meeting_institution_type_ids = [$this->publicType->id];
        $settings->save();

        $this->publicInstitution = Institution::factory()->for($this->tenant)->create();
        $this->publicInstitution->types()->attach($this->publicType);

        $this->rep = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $this->ownInstitution = $this->rep->current_duties->first()->institution;

        InstitutionCheckIn::factory()->for($this->publicInstitution)->create();
        $this->publicInstitution->duties()->save(Duty::factory()->make())->users()->attach(User::factory()->create()->id, ['start_date' => now()->subYear()]);
    });

    test('a rep gets public bodies of other padaliniai and their own, trimmed to what they may read', function (): void {
        $rows = collect(
            asUser($this->rep)->getJson(route('api.v1.admin.visak.gantt', [
                'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
            ]))->assertSuccessful()->json('data')
        )->keyBy('id');

        expect($rows->keys()->sort()->values()->all())
            ->toEqual(collect([(string) $this->publicInstitution->id, (string) $this->ownInstitution->id])->sort()->values()->all());

        $public = $rows->get((string) $this->publicInstitution->id);
        expect($public['authorized'])->toBeFalse()
            ->and($public['activity_status'])->toBeNull()
            ->and($public['check_ins'])->toHaveCount(1)
            ->and($public['duties'][0]['users'][0])->toHaveKeys(['id', 'name'])->not->toHaveKey('last_action')
            ->and($rows->get((string) $this->ownInstitution->id))->not->toHaveKey('authorized')
            ->and($rows->get((string) $this->ownInstitution->id)['activity_status'])->not->toBeNull();
    });

    test('a padalinys reader gets their padalinys in full and only public bodies elsewhere', function (): void {
        $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->ownInstitution->tenant);
        $otherInstitution = Institution::factory()->for($this->ownInstitution->tenant)->create();

        $rows = collect(
            asUser($coordinator)->getJson(route('api.v1.admin.visak.gantt', [
                'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
            ]))->assertSuccessful()->json('data')
        )->keyBy('id');

        expect($rows->has((string) $otherInstitution->id))->toBeTrue()
            ->and($rows->get((string) $otherInstitution->id))->not->toHaveKey('authorized')
            ->and($rows->has((string) $this->institution->id))->toBeFalse()
            ->and($rows->get((string) $this->publicInstitution->id)['authorized'])->toBeFalse();
    });

    test('a rep sees public meetings without their recording state, and no meetings of closed bodies', function (): void {
        $public = Meeting::factory()->create(['title' => 'Viešas', 'start_time' => now()->subDays(5)]);
        $public->institutions()->attach($this->publicInstitution->id);
        AgendaItem::factory()->for($public)->create();

        $closed = Meeting::factory()->create(['title' => 'Uždaras', 'start_time' => now()->subDays(5)]);
        $closed->institutions()->attach($this->institution->id);

        $own = Meeting::factory()->create(['title' => 'Savas', 'start_time' => now()->subDays(5)]);
        $own->institutions()->attach($this->ownInstitution->id);

        $meetings = collect(
            asUser($this->rep)->getJson(route('api.v1.admin.visak.meetings', [
                'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
                'from' => now()->subMonth()->toDateString(),
                'until' => now()->addMonth()->toDateString(),
            ]))->assertSuccessful()->json('data')
        )->keyBy('title');

        expect($meetings->keys()->sort()->values()->all())->toEqual(['Savas', 'Viešas'])
            ->and($meetings['Viešas']['completion_status'])->toBeNull()
            ->and($meetings['Viešas']['has_report'])->toBeFalse()
            ->and($meetings['Viešas']['agenda_items_count'])->toBe(1)
            ->and($meetings['Savas']['completion_status'])->not->toBeNull();
    });

    test('a private agenda item is redacted per reader, even when readers share a cached window', function (): void {
        $pastMember = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $this->publicInstitution->duties->first()->users()->attach($pastMember->id, ['start_date' => now()->subYear(), 'end_date' => now()->subDays(2)]);

        $meeting = Meeting::factory()->create(['title' => 'Su slaptu klausimu', 'start_time' => now()->subDays(5)]);
        $meeting->institutions()->attach($this->publicInstitution->id);
        AgendaItem::factory()->for($meeting)->create(['is_private' => true, 'title' => ['lt' => 'Slaptas klausimas', 'en' => 'Secret item'], 'public_title' => null]);

        $itemFor = function (User $user) use ($meeting): array {
            $meetings = asUser($user)->getJson(route('api.v1.admin.visak.meetings', [
                'tenant_ids' => [$this->tenant->id],
                'from' => now()->subMonth()->toDateString(),
                'until' => now()->addMonth()->toDateString(),
            ]))->assertSuccessful()->json('data');

            return collect($meetings)->firstWhere('id', (string) $meeting->id)['agenda_items'][0];
        };

        $viewerFirst = $itemFor($this->rep);
        $memberAfter = $itemFor($pastMember);
        $viewerAgain = $itemFor($this->rep);

        expect($viewerFirst)->toMatchArray(['is_redacted' => true])->and($viewerFirst['title'])->not->toBe('Slaptas klausimas')
            ->and($memberAfter['title'])->toBe('Slaptas klausimas')
            ->and($viewerAgain)->toMatchArray(['is_redacted' => true]);
    });

    test('a body that only links to the rep\'s own appears read-only, its meetings without agendas', function (): void {
        $related = Institution::factory()->for($this->tenant)->create();
        InstitutionLink::factory()->create(['source_institution_id' => $related->id, 'target_institution_id' => $this->ownInstitution->id]);
        $meeting = Meeting::factory()->create(['title' => 'Susijęs', 'start_time' => now()->subDays(5)]);
        $meeting->institutions()->attach($related->id);
        AgendaItem::factory()->for($meeting)->create();
        $tenantIds = [$this->tenant->id, $this->ownInstitution->tenant_id];

        $rows = collect(asUser($this->rep)->getJson(route('api.v1.admin.visak.gantt', ['tenant_ids' => $tenantIds]))->json('data'))->keyBy('id');
        $meetings = collect(asUser($this->rep)->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => $tenantIds,
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ]))->json('data'))->keyBy('title');

        expect($rows->get((string) $related->id)['authorized'])->toBeFalse()
            ->and($meetings['Susijęs']['authorized'])->toBeFalse()
            ->and($meetings['Susijęs']['agenda_items_count'])->toBe(0);
    });

    test('rows carry the last held and next meeting, and a vote added later counts as activity', function (): void {
        // Activity counts what a person did, so the writes need a causer.
        $this->actingAs($this->rep);
        $this->travelTo(now()->subDays(20));
        $held = Meeting::factory()->create(['start_time' => now()->addDays(10)]);
        $held->institutions()->attach($this->ownInstitution->id);
        $upcoming = Meeting::factory()->create(['start_time' => now()->addDays(25)]);
        $upcoming->institutions()->attach($this->ownInstitution->id);
        $agendaItem = AgendaItem::factory()->for($held)->create();
        $this->travelBack();

        $this->travelTo(now()->subDays(2));
        Vote::factory()->for($agendaItem)->create();
        $this->travelBack();

        // A migration or bulk script rewriting rows is not activity.
        DB::table('meetings')->update(['updated_at' => now()]);

        $row = collect(asUser($this->rep)->getJson(route('api.v1.admin.visak.gantt', [
            'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
        ]))->json('data'))->firstWhere('id', (string) $this->ownInstitution->id);

        expect($row['last_meeting_date'])->toBe($held->start_time->toISOString())
            ->and($row['next_meeting_date'])->toBe($upcoming->start_time->toISOString())
            ->and(Carbon\Carbon::parse($row['last_activity_at'])->tz(config('app.timezone'))->toDateString())->toBe(now()->subDays(2)->toDateString());
    });

    test('a new check-in counts as activity', function (): void {
        $this->travelTo(now()->subDays(20));
        $meeting = Meeting::factory()->create(['start_time' => now()->addDays(10)]);
        $meeting->institutions()->attach($this->ownInstitution->id);
        $this->travelBack();

        $this->travelTo(now()->subDays(3));
        InstitutionCheckIn::factory()->create(['institution_id' => $this->ownInstitution->id, 'user_id' => $this->rep->id]);
        $this->travelBack();

        $row = collect(asUser($this->rep)->getJson(route('api.v1.admin.visak.gantt', [
            'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
        ]))->json('data'))->firstWhere('id', (string) $this->ownInstitution->id);

        expect(Carbon\Carbon::parse($row['last_activity_at'])->tz(config('app.timezone'))->toDateString())->toBe(now()->subDays(3)->toDateString());
    });

    test('a body the rep\'s own links to appears in full', function (): void {
        $related = Institution::factory()->for($this->tenant)->create();
        InstitutionLink::factory()->create(['source_institution_id' => $this->ownInstitution->id, 'target_institution_id' => $related->id]);

        $rows = collect(asUser($this->rep)->getJson(route('api.v1.admin.visak.gantt', [
            'tenant_ids' => [$this->tenant->id, $this->ownInstitution->tenant_id],
        ]))->json('data'))->keyBy('id');

        expect($rows->get((string) $related->id))->not->toHaveKey('authorized')
            ->and($rows->get((string) $related->id)['activity_status'])->not->toBeNull();
    });

    test('a body the user administers as secretary appears in full', function (): void {
        $secretary = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $administered = Institution::factory()->for($this->tenant)->create();
        $secretary->secretariedInstitutions()->attach($administered, ['cadence_id' => Cadence::factory()->create()->id]);

        $rows = collect(asUser($secretary)->getJson(route('api.v1.admin.visak.gantt', [
            'tenant_ids' => [$this->tenant->id],
        ]))->json('data'))->keyBy('id');

        expect($rows->get((string) $administered->id))->not->toBeNull()->not->toHaveKey('authorized');
    });

    test('a cached window of full meetings is never served to a user who may only see them as public', function (): void {
        $public = Meeting::factory()->create(['title' => 'Viešas', 'start_time' => now()->subDays(5)]);
        $public->institutions()->attach($this->publicInstitution->id);
        $params = [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ];
        asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', $params))
            ->assertJsonPath('data.0.completion_status', fn ($status) => $status !== null);

        $response = asUser($this->rep)->getJson(route('api.v1.admin.visak.meetings', $params));

        $response->assertJsonPath('data.0.title', 'Viešas')
            ->assertJsonPath('data.0.completion_status', null);
    });

    test('the stats timeline stays closed to padaliniai the user does not manage', function (): void {
        asUser($this->rep)
            ->getJson(route('api.v1.admin.visak.timeline', ['tenant_ids' => [$this->tenant->id]]))
            ->assertForbidden();
    });
});

test('gantt rows carry their institution types for type sections', function (): void {
    $type = InstitutionType::factory()->create(['title' => ['lt' => 'Studijų kolegija', 'en' => 'Study board']]);
    $this->institution->types()->attach($type);

    $row = collect(asUser($this->admin)->getJson(route('api.v1.admin.visak.gantt', ['tenant_ids' => [$this->tenant->id]]))
        ->assertSuccessful()->json('data'))->firstWhere('id', (string) $this->institution->id);

    expect($row['types'])->toBe([['id' => (string) $type->id, 'title' => 'Studijų kolegija']]);
});

test('gantt meetings cost the same number of queries however many there are', function (): void {
    $queriesFor = function (int $count): int {
        Meeting::factory()->count($count)->create(['start_time' => now()->subDays(3)])
            ->each(fn (Meeting $meeting) => $meeting->institutions()->attach($this->institution->id));
        Meeting::query()->get()->each(fn (Meeting $meeting) => AgendaItem::factory()->for($meeting)->create());
        Cache::flush();

        DB::enableQueryLog();
        asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->toDateString(),
        ]))->assertSuccessful();
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        return $count;
    };

    $queriesFor(0); // The first request also warms the auth and permission caches.

    expect($queriesFor(4))->toBe($queriesFor(1));
});

describe('activity requests on the gantt', function (): void {
    beforeEach(function (): void {
        $this->rep = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $this->ownInstitution = $this->rep->current_duties->first()->institution;
        $this->spansFor = fn (User $user, array $tenantIds) => collect(asUser($user)
            ->getJson(route('api.v1.admin.visak.activityRequests', ['tenant_ids' => $tenantIds]))
            ->assertSuccessful()->json('data'));
    });

    test('an asker sees every open request, one span per institution and campaign', function (): void {
        InstitutionActivityRequest::factory()->for($this->institution)->create(['period_start' => '2026-08-01', 'period_end' => '2026-09-15']);
        InstitutionActivityRequest::factory()->for($this->institution)->create(['period_start' => '2026-07-15', 'period_end' => '2026-09-01']);
        InstitutionActivityRequest::factory()->for($this->institution)->create(['campaign_type' => InstitutionActivityCampaign::MissingMeetings, 'period_start' => '2026-02-01', 'period_end' => '2026-09-15']);
        InstitutionActivityRequest::factory()->for($this->institution)->expired()->create();
        InstitutionActivityRequest::factory()->for($this->institution)->answered()->create();
        InstitutionActivityRequest::factory()->for($this->institution)->create(['resolved_at' => now()]);

        $spans = ($this->spansFor)($this->admin, [$this->tenant->id])->keyBy('campaign');

        expect($spans)->toHaveCount(2)
            ->and($spans['activity_confirmation'])->toMatchArray([
                'institution_id' => (string) $this->institution->id,
                'period_start' => '2026-07-15',
                'period_end' => '2026-09-15',
                'recipients_count' => 2,
            ])
            ->and($spans['missing_meetings']['recipients_count'])->toBe(1);
    });

    test('anyone else sees only the requests sent to them', function (): void {
        InstitutionActivityRequest::factory()->for($this->ownInstitution)->create(['recipient_id' => $this->rep->id]);
        InstitutionActivityRequest::factory()->for($this->ownInstitution)->create();
        InstitutionActivityRequest::factory()->for($this->institution)->create(['recipient_id' => $this->rep->id]);

        $spans = ($this->spansFor)($this->rep, [$this->ownInstitution->tenant_id, $this->tenant->id]);

        // The other padalinys' institution is not on this rep's Gantt at all, so its request stays out.
        expect($spans)->toHaveCount(1)
            ->and($spans[0])->toMatchArray(['institution_id' => (string) $this->ownInstitution->id, 'recipients_count' => 1]);
    });

    test('requires an authenticated user', function (): void {
        $this->getJson(route('api.v1.admin.visak.activityRequests', ['tenant_ids' => [$this->tenant->id]]))->assertUnauthorized();
    });
});
