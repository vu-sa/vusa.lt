<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());
});

test('unauthenticated request is rejected', function (): void {
    $this->getJson(route('api.v1.admin.meetings.agendaItems', $this->meeting))
        ->assertStatus(401);
});

test('user without access to the meeting gets 403', function (): void {
    $user = makeUser($this->tenant);

    asUser($user)
        ->getJson(route('api.v1.admin.meetings.agendaItems', $this->meeting))
        ->assertStatus(403);
});

test('authorized admin receives only timed agenda items', function (): void {
    $admin = makeAdminUser($this->tenant);

    AgendaItem::factory()->for($this->meeting)->create([
        'title' => 'Su laiku',
        'start_time' => '18:30:00',
        'end_time' => '19:00:00',
        'order' => 2,
    ]);
    AgendaItem::factory()->for($this->meeting)->create([
        'title' => 'Be laiko',
        'start_time' => null,
        'order' => 1,
    ]);

    asUser($admin)
        ->getJson(route('api.v1.admin.meetings.agendaItems', $this->meeting))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Su laiku')
        ->assertJsonPath('data.0.startTime', '18:30:00')
        ->assertJsonPath('data.0.endTime', '19:00:00')
        ->assertJsonMissing(['title' => 'Be laiko']);
});
