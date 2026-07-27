<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = makeAdminUser(Tenant::query()->first());
    $this->tenant = Tenant::factory()->create(['type' => 'padalinys']);
    $this->institution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Tiesioginė institucija', 'en' => 'Direct institution'],
    ]);
});

test('timeline requires an authenticated user', function () {
    $this->getJson(route('api.v1.admin.visak.timeline', [
        'tenant_ids' => [$this->tenant->id],
    ]))->assertUnauthorized();
});

test('timeline rejects tenants outside the visible scope', function () {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertForbidden();
});

test('timeline returns direct institutions with summaries but no meetings or relations', function () {
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
    expect($institution)->not->toHaveKey('meetings');
    expect($institution)->toHaveKeys(['activity_status', 'check_ins', 'duties', 'tenant']);

    // Summaries remain fully populated
    expect($data['institution_summary'])->toHaveKeys(['all', 'needs_attention', 'overdue', 'approaching', 'no_activity', 'current']);
    expect($data['representative_activity']['stats'])->toHaveKeys(['total', 'activeToday', 'activeLast7Days', 'activeLast30Days', 'neverLoggedIn']);
});

test('meetings requires an authenticated user', function () {
    $this->getJson(route('api.v1.admin.visak.meetings', [
        'tenant_ids' => [$this->tenant->id],
        'from' => now()->subMonth()->toDateString(),
        'until' => now()->addMonth()->toDateString(),
    ]))->assertUnauthorized();
});

test('meetings rejects tenants outside the visible scope', function () {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.meetings', [
            'tenant_ids' => [$this->tenant->id],
            'from' => now()->subMonth()->toDateString(),
            'until' => now()->addMonth()->toDateString(),
        ]))
        ->assertForbidden();
});

test('meetings validates the date window', function () {
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

test('meetings returns only in-window meetings of the requested tenants, trimmed for the Gantt', function () {
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

    expect($meeting['id'])->toBe((string) $inWindow->id);
    expect($meeting['institution_id'])->toBe((string) $this->institution->id);
    expect($meeting['title'])->toBe('In window');
    expect($meeting)->toHaveKeys(['start_time', 'type_slug', 'completion_status', 'has_report', 'has_protocol', 'agenda_items', 'agenda_items_count']);
    expect($meeting['agenda_items_count'])->toBe(5);
    expect($meeting['agenda_items'])->toHaveCount(4);

    // Agenda items carry only the main-vote fields the Gantt tooltip renders
    $item = $meeting['agenda_items'][0];
    expect($item)->toHaveKeys(['id', 'title', 'type', 'student_vote', 'decision']);
    expect($item)->not->toHaveKey('student_benefit');
    expect($item)->not->toHaveKey('votes');
    expect($item['student_vote'])->toBe('positive');
    expect($item['decision'])->toBe('positive');
});

test('meetings are cached per window and refresh bypasses the cache', function () {
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

test('representatives are searched and paginated without loading the full list', function () {
    $duty = Duty::factory()->for($this->institution)->create();

    collect([
        ['name' => 'First Representative', 'email' => 'first-representative@example.test'],
        ['name' => 'Second Representative', 'email' => 'second-representative@example.test'],
        ['name' => 'Third Representative', 'email' => 'third-representative@example.test'],
    ])->each(function (array $attributes) use ($duty) {
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
