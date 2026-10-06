<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->resource = Resource::factory()->for($this->tenant)->create([
        'capacity' => 5,
        'is_reservable' => true,
    ]);

    $this->start = now()->addDay()->startOfHour();
    $this->end = (clone $this->start)->addHours(4);
});

test('guests cannot query availability', function (): void {
    $this->postJson(route('api.v1.admin.resources.availability'), [
        'ids' => [$this->resource->id],
        'start' => $this->start->getTimestampMs(),
        'end' => $this->end->getTimestampMs(),
    ])->assertUnauthorized();
});

test('returns full capacity when there are no overlapping reservations', function (): void {
    $response = asUser($this->user)->postJson(route('api.v1.admin.resources.availability'), [
        'ids' => [$this->resource->id],
        'start' => $this->start->getTimestampMs(),
        'end' => $this->end->getTimestampMs(),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath("data.{$this->resource->id}.capacity", 5)
        ->assertJsonPath("data.{$this->resource->id}.lowestCapacityAtDateTimeRange", 5)
        ->assertJsonPath("data.{$this->resource->id}.reservations", []);
});

test('subtracts an overlapping reservation from the available capacity', function (): void {
    $reservation = Reservation::factory()->create();
    $reservation->resources()->attach($this->resource->id, [
        'quantity' => 2,
        'state' => 'reserved',
        'start_time' => (clone $this->start)->addHour(),
        'end_time' => (clone $this->start)->addHours(2),
    ]);

    $response = asUser($this->user)->postJson(route('api.v1.admin.resources.availability'), [
        'ids' => [$this->resource->id],
        'start' => $this->start->getTimestampMs(),
        'end' => $this->end->getTimestampMs(),
    ]);

    $response->assertOk()
        ->assertJsonPath("data.{$this->resource->id}.lowestCapacityAtDateTimeRange", 3)
        ->assertJsonPath("data.{$this->resource->id}.reservations.0.quantity", 2);
});

test('validates that start is before end', function (): void {
    asUser($this->user)->postJson(route('api.v1.admin.resources.availability'), [
        'ids' => [$this->resource->id],
        'start' => $this->end->getTimestampMs(),
        'end' => $this->start->getTimestampMs(),
    ])->assertStatus(422);
});
