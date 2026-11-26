<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

test('reservable scope returns only reservable resources', function (): void {
    $reservableA = Resource::factory()->for($this->tenant)->create(['is_reservable' => true]);
    $nonReservable = Resource::factory()->for($this->tenant)->create(['is_reservable' => false]);
    $reservableB = Resource::factory()->for($this->tenant)->create(['is_reservable' => true]);

    $ids = Resource::reservable()->pluck('id')->all();

    expect($ids)->toContain($reservableA->id, $reservableB->id)
        ->not->toContain($nonReservable->id);
});

test('leftCapacityAtTime subtracts only overlapping active reservations', function (): void {
    $resource = Resource::factory()->for($this->tenant)->create(['capacity' => 10]);
    $checkAt = Carbon::parse('2026-06-15 11:00:00');

    $active = Reservation::factory()->create();
    $resource->reservations()->attach($active->id, [
        'start_time' => Carbon::parse('2026-06-15 10:00:00'),
        'end_time' => Carbon::parse('2026-06-15 12:00:00'),
        'quantity' => 3,
        'state' => 'reserved',
    ]);

    $cancelled = Reservation::factory()->create();
    $resource->reservations()->attach($cancelled->id, [
        'start_time' => Carbon::parse('2026-06-15 10:00:00'),
        'end_time' => Carbon::parse('2026-06-15 12:00:00'),
        'quantity' => 4,
        'state' => 'cancelled',
    ]);

    $later = Reservation::factory()->create();
    $resource->reservations()->attach($later->id, [
        'start_time' => Carbon::parse('2026-06-15 14:00:00'),
        'end_time' => Carbon::parse('2026-06-15 16:00:00'),
        'quantity' => 5,
        'state' => 'reserved',
    ]);

    expect($resource->leftCapacityAtTime($checkAt))->toBe(7);
});
