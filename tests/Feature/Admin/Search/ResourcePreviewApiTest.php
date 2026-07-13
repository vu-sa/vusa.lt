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

test('any authenticated user can preview a resource', function () {
    $user = makeUser($this->tenant);

    asUser($user)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['upcoming_reservations', 'previous_reservations', 'managers'],
        ]);
});

test('authorized admin receives the preview payload', function () {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['upcoming_reservations', 'previous_reservations', 'managers'],
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

/**
 * An item whose window has ended but which nobody has returned, rejected or cancelled is still
 * open business, so it stays in the active list — the preview flags it as unresolved instead of
 * hiding it. It is not terminal either, so dropping it here would lose it from both lists.
 */
test('preview keeps ended active reservations in the active list', function () {
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
        ->assertJsonPath('data.upcoming_reservations.0.name', 'Past event')
        ->assertJsonPath('data.upcoming_reservations.0.state', 'reserved');
});

test('preview includes terminal past reservations in previous list', function () {
    $admin = makeAdminUser($this->tenant);

    $reservation = Reservation::factory()->create(['name' => 'Returned event']);
    $this->resource->reservations()->attach($reservation->id, [
        'quantity' => 2,
        'state' => 'returned',
        'start_time' => now()->subDays(3),
        'end_time' => now()->subDays(2),
    ]);

    asUser($admin)
        ->getJson(route('api.v1.admin.resources.preview', $this->resource))
        ->assertStatus(200)
        ->assertJsonPath('data.previous_reservations.0.name', 'Returned event')
        ->assertJsonPath('data.previous_reservations.0.quantity', 2)
        ->assertJsonPath('data.previous_reservations.0.state', 'returned');
});
