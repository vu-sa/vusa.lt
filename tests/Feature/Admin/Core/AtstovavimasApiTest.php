<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\InstitutionActivityStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('timeline returns direct institutions with summaries but no meetings or relations', function (): void {
    $relatedTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $relatedInstitution = Institution::factory()->for($relatedTenant)->create([
        'name' => ['lt' => 'Susijusi institucija', 'en' => 'Related institution'],
    ]);
    $relationship = Relationship::query()->create([
        'name' => 'Test relationship',
        'slug' => 'test-relationship',
    ]);

    Relationshipable::query()->create([
        'relationship_id' => $relationship->id,
        'relationshipable_type' => Institution::class,
        'relationshipable_id' => $this->institution->id,
        'related_model_id' => $relatedInstitution->id,
    ]);

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
    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertSuccessful()
        ->assertJsonCount(90, 'data');
});

test('status history reflects day-to-day changes in institution status', function (): void {
    $this->institution->update(['meeting_periodicity_days' => 10]);

    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(90)]);
    $meeting->institutions()->attach($this->institution->id);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 90,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(90, 'data');

    $series = $response->json('data');

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
    $meeting = Meeting::factory()->create(['start_time' => now()->subDays(90)]);
    $meeting->institutions()->attach($this->institution->id);

    // Never met, no check-ins: the perf shortcut should still count it correctly
    // every single day, purely via the fixed idle count, not a per-day resolve().
    Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Neaktyvi institucija', 'en' => 'Idle institution'],
    ]);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => 90,
        ]))
        ->assertSuccessful()
        ->assertJsonCount(90, 'data');

    $series = $response->json('data');

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

test('status history caches for longer than the live timeline, and refresh bypasses it', function (): void {
    $params = [
        'tenant_ids' => [$this->tenant->id],
        'days' => 30,
    ];

    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', $params))
        ->assertSuccessful()
        ->assertJsonPath('data.29.all', 1);

    // A newly created institution is not reflected without a refresh...
    Institution::factory()->for($this->tenant)->create();

    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', $params))
        ->assertSuccessful()
        ->assertJsonPath('data.29.all', 1);

    // ...but is picked up once the cache is bypassed
    asUser($this->admin)->getJson(route('api.v1.admin.visak.timeline.history', [...$params, 'refresh' => 1]))
        ->assertSuccessful()
        ->assertJsonPath('data.29.all', 2);
});

test('status history sweep matches resolve() day-by-day, including meeting/check-in overtaking and upcoming-meeting transitions', function (): void {
    // A: only a meeting, initially in the future relative to older days in the
    // range, then in the past — exercises the meeting cursor crossing its
    // "upcoming" boundary partway through the sweep.
    $institutionA = $this->institution;
    $institutionA->update(['meeting_periodicity_days' => 15]);
    $meetingA = Meeting::factory()->create(['start_time' => now()->subDays(40)]);
    $meetingA->institutions()->attach($institutionA->id);

    // B: only a check-in — not yet started, then covering, then completed and aging.
    $institutionB = Institution::factory()->for($this->tenant)->create([
        'meeting_periodicity_days' => 20,
    ]);
    InstitutionCheckIn::factory()->for($institutionB)->create([
        'start_date' => now()->subDays(55)->toDateString(),
        'end_date' => now()->subDays(40)->toDateString(),
    ]);

    // C: a meeting, later overtaken by a check-in as the "latest activity" —
    // exercises the checkInIsLatestActivity comparison against a real meeting.
    $institutionC = Institution::factory()->for($this->tenant)->create([
        'meeting_periodicity_days' => 10,
    ]);
    $meetingC = Meeting::factory()->create(['start_time' => now()->subDays(50)]);
    $meetingC->institutions()->attach($institutionC->id);
    InstitutionCheckIn::factory()->for($institutionC)->create([
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->subDays(10)->toDateString(),
    ]);

    $institutions = collect([$institutionA, $institutionB, $institutionC]);
    $days = 60;

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline.history', [
            'tenant_ids' => [$this->tenant->id],
            'days' => $days,
        ]))
        ->assertSuccessful()
        ->assertJsonCount($days, 'data');

    $series = $response->json('data');

    // Ground truth: call resolve() directly for every institution and every date,
    // completely independent of the sweep implementation being tested.
    $activityStatusService = app(InstitutionActivityStatusService::class);
    $today = now()->startOfDay();

    for ($offset = $days - 1; $offset >= 0; $offset--) {
        $date = $today->copy()->subDays($offset)->endOfDay();
        $dayIndex = $days - 1 - $offset;

        $expected = ['overdue' => 0, 'approaching' => 0, 'no_activity' => 0, 'needs_attention' => 0];
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
            'current' => 3 - $expected['needs_attention'],
        ]);
    }
});

test('meetings requires an authenticated user', function (): void {
    $this->getJson(route('api.v1.admin.visak.meetings', [
        'tenant_ids' => [$this->tenant->id],
        'from' => now()->subMonth()->toDateString(),
        'until' => now()->addMonth()->toDateString(),
    ]))->assertUnauthorized();
});

test('meetings rejects tenants outside the visible scope', function (): void {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ]))
        ->assertForbidden();
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
        ->toHaveKeys(['start_time', 'type_slug', 'completion_status', 'has_report', 'has_protocol', 'agenda_items', 'agenda_items_count'])
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

    // A meeting created after the cached response is not visible without a refresh
    $second = Meeting::factory()->create(['title' => 'Second', 'start_time' => now()->subDays(4)]);
    $second->institutions()->attach($this->institution->id);

    asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', $params))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    asUser($this->admin)->getJson(route('api.v1.admin.visak.meetings', [...$params, 'refresh' => 1]))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

test('representatives are searched and paginated without loading the full list', function (): void {
    $duty = Duty::factory()->for($this->institution)->create();

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
