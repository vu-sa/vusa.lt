<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

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
        ->assertJsonPath("data.{$this->resource->id}.reservations.0.quantity", 2)
        ->assertJsonPath("data.{$this->resource->id}.reservations.0.name", null);
});

test('validates that start is before end', function (): void {
    asUser($this->user)->postJson(route('api.v1.admin.resources.availability'), [
        'ids' => [$this->resource->id],
        'start' => $this->end->getTimestampMs(),
        'end' => $this->start->getTimestampMs(),
    ])->assertStatus(422);
});

test('availability query count stays constant from one reservation to six', function (): void {
    $viewerTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
    $viewer = makeTenantUserWithRole('Išteklių administratorius', $viewerTenant);
    $participant = makeUser($this->tenant);
    $start = now()->subDays(3)->startOfDay();
    $end = $start->copy()->addHours(12);
    $resources = Resource::factory()->for($this->tenant)->count(2)->create(['capacity' => 10, 'is_reservable' => true]);

    foreach ([1, 6] as $index => $count) {
        foreach (range(1, $count) as $minute) {
            $reservation = Reservation::factory()->create();
            $reservation->users()->attach($participant->id);
            $reservation->resources()->attach($resources[$index]->id, [
                'quantity' => 1,
                'state' => 'reserved',
                'start_time' => $start->copy()->addHours(4)->addMinutes($minute),
                'end_time' => $start->copy()->addHours(7)->addMinutes($minute),
            ]);
        }
    }

    $fetch = fn (Resource $resource): TestResponse => asUser($viewer)
        ->postJson(route('api.v1.admin.resources.availability'), [
            'ids' => [$resource->id],
            'start' => $start->getTimestampMs(),
            'end' => $end->getTimestampMs(),
        ])->assertOk();

    $fetch($resources[0]);
    $fetch($resources[1]);

    DB::enableQueryLog();

    try {
        DB::flushQueryLog();
        $small = $fetch($resources[0]);
        $smallCount = count(DB::getQueryLog());
        DB::flushQueryLog();
        $large = $fetch($resources[1]);
        $largeCount = count(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    $small->assertJsonCount(1, "data.{$resources[0]->id}.reservations")
        ->assertJsonCount(1, "data.{$resources[0]->id}.discrepancies");

    $path = "data.{$resources[1]->id}";
    $large->assertJsonCount(6, $path.'.reservations')
        ->assertJsonCount(6, $path.'.discrepancies')
        ->assertJsonPath($path.'.reservations.0.name', null)
        ->assertJsonPath($path.'.discrepancies.0.id', null)
        ->assertJsonPath($path.'.lowestCapacityAtDateTimeRange', 10)
        ->assertJsonPath($path.'.strictLowestCapacityAtDateTimeRange', 4);
    expect($largeCount)->toBe($smallCount);
});

test('reservation rows use strict overlap at the requested millisecond boundaries', function (): void {
    $start = Carbon::parse('2026-01-15 13:00:00');
    $end = $start->copy()->addHours(4);
    $pivotStart = $start->copy()->addHour();
    $pivotEnd = $pivotStart->copy()->addHour();
    $reservation = Reservation::factory()->create();
    $reservation->users()->attach($this->user->id);
    $reservation->resources()->attach($this->resource->id, [
        'quantity' => 2,
        'state' => 'reserved',
        'start_time' => $pivotStart,
        'end_time' => $pivotEnd,
    ]);

    $fetch = fn (int $start, int $end): TestResponse => asUser($this->user)
        ->postJson(route('api.v1.admin.resources.availability'), [
            'ids' => [$this->resource->id],
            'start' => $start,
            'end' => $end,
        ])->assertOk();

    $path = "data.{$this->resource->id}.reservations";
    $fetch($start->getTimestampMs(), $pivotStart->getTimestampMs())->assertJsonCount(0, $path);
    $fetch($start->getTimestampMs(), $pivotStart->getTimestampMs() + 1)
        ->assertJsonCount(1, $path)
        ->assertJsonPath($path.'.0.id', $reservation->id);
    $fetch($pivotEnd->getTimestampMs(), $end->getTimestampMs())->assertJsonCount(0, $path);
});
