<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});

test('unauthenticated request is rejected', function () {
    $this->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(401);
});

test('user without access to the resource gets 403', function () {
    $user = makeUser($this->tenant);

    asUser($user)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(403);
});

test('authorized admin receives the preview payload', function () {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['upcoming_reservations', 'managers'],
        ]);
});

test('preview lists upcoming active reservations', function () {
    $admin = makeAdminUser($this->tenant);

    $reservation = Reservation::factory()->create(['name' => 'Upcoming event']);
    $this->resource->reservations()->attach($reservation->id, [
        'quantity' => 2,
        'state' => 'reserved',
        'start_time' => now()->addDay(),
        'end_time' => now()->addDays(2),
    ]);

    asUser($admin)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJsonPath('data.upcoming_reservations.0.name', 'Upcoming event')
        ->assertJsonPath('data.upcoming_reservations.0.quantity', 2);
});

test('preview omits past reservations', function () {
    $admin = makeAdminUser($this->tenant);

    $reservation = Reservation::factory()->create(['name' => 'Past event']);
    $this->resource->reservations()->attach($reservation->id, [
        'quantity' => 1,
        'state' => 'reserved',
        'start_time' => now()->subDays(3),
        'end_time' => now()->subDays(2),
    ]);

    asUser($admin)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJsonPath('data.upcoming_reservations', []);
});
