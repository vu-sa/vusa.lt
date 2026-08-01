<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());
});

test('unauthenticated request is rejected', function (): void {
    $this->getJson(route('api.v1.admin.meetings.preview', $this->meeting))
        ->assertStatus(401);
});

test('user without access to the meeting gets 403', function (): void {
    $user = makeUser($this->tenant);

    asUser($user)
        ->getJson(route('api.v1.admin.meetings.preview', $this->meeting))
        ->assertStatus(403);
});

test('authorized admin receives the preview payload', function (): void {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->getJson(route('api.v1.admin.meetings.preview', $this->meeting))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['institutions', 'agenda_items', 'representatives'],
        ]);
});
