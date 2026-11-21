<?php

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Services\ResourceCapacityCalculator;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-01-15 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makeCapacityTestResource(): Resource
{
    return Resource::factory()->for(Tenant::query()->firstOrFail())->create([
        'capacity' => 10,
        'is_reservable' => true,
    ]);
}

function reserveCapacityForTest(
    Resource $resource,
    string $start,
    string $end,
    int $quantity,
    string $state = 'reserved',
    ?Reservation $reservation = null,
): ReservationResource {
    $reservation ??= Reservation::factory()->create([
        'start_time' => $start,
        'end_time' => $end,
    ]);

    $pivot = new ReservationResource([
        'resource_id' => $resource->id,
        'reservation_id' => $reservation->id,
        'start_time' => $start,
        'end_time' => $end,
        'quantity' => $quantity,
        'state' => $state,
    ]);
    $pivot->saveQuietly();

    return $pivot;
}

test('capacity changes on the correct side of a reservation start and end', function (): void {
    $resource = makeCapacityTestResource();
    $calculator = new ResourceCapacityCalculator($resource);
    reserveCapacityForTest($resource, '2026-01-15 13:00:00', '2026-01-15 15:00:00', 3);

    expect($calculator->calculateCapacityAtTimeArray(Carbon::parse('2026-01-15 13:00:00')))
        ->toBe(['before' => 10, 'after' => 7]);
    expect($calculator->calculateCapacityAtTimeArray(Carbon::parse('2026-01-15 15:00:00')))
        ->toBe(['before' => 7, 'after' => 10]);
});

test('adjacent reservations each count on their own side of the shared boundary', function (): void {
    $resource = makeCapacityTestResource();
    $calculator = new ResourceCapacityCalculator($resource);
    reserveCapacityForTest($resource, '2026-01-15 13:00:00', '2026-01-15 14:00:00', 2);
    reserveCapacityForTest($resource, '2026-01-15 14:00:00', '2026-01-15 15:00:00', 5);

    expect($calculator->calculateCapacityAtTimeArray(Carbon::parse('2026-01-15 14:00:00')))
        ->toBe(['before' => 8, 'after' => 5]);
});

test('capacity counts each active state in strict mode and ignores it once elapsed in flexible mode', function (string $state, int $strictCapacity): void {
    $resource = makeCapacityTestResource();
    $calculator = new ResourceCapacityCalculator($resource);
    reserveCapacityForTest($resource, '2026-01-15 09:00:00', '2026-01-15 11:00:00', 3, $state);
    $time = Carbon::parse('2026-01-15 10:00:00');

    expect($calculator->calculateCapacityAtTimeArray($time, ignoreTimeEndedActive: false))
        ->toBe(['before' => $strictCapacity, 'after' => $strictCapacity]);
    expect($calculator->calculateCapacityAtTimeArray($time, ignoreTimeEndedActive: true))
        ->toBe(['before' => 10, 'after' => 10]);
})->with([
    'created' => ['created', 7],
    'reserved' => ['reserved', 7],
    'lent' => ['lent', 7],
    'cancelled' => ['cancelled', 10],
]);

test('reservation exceptions apply to listed resources and a pivot exception removes only that line', function (): void {
    $resource = makeCapacityTestResource();
    $calculator = new ResourceCapacityCalculator($resource);
    $reservation = Reservation::factory()->create([
        'start_time' => '2026-01-15 13:00:00',
        'end_time' => '2026-01-15 18:00:00',
    ]);
    $excludedPivot = reserveCapacityForTest($resource, '2026-01-15 13:00:00', '2026-01-15 16:00:00', 2, reservation: $reservation);
    $retainedPivot = reserveCapacityForTest($resource, '2026-01-15 14:00:00', '2026-01-15 17:00:00', 3, reservation: $reservation);
    $otherPivot = reserveCapacityForTest($resource, '2026-01-15 15:00:00', '2026-01-15 18:00:00', 1);
    $time = Carbon::parse('2026-01-15 15:30:00');

    expect($calculator->calculateCapacityAtTimeArray($time, exceptReservations: [$reservation->id]))
        ->toBe(['before' => 4, 'after' => 4]);
    expect($calculator->calculateCapacityAtTimeArray($time, exceptResources: [$resource->id]))
        ->toBe(['before' => 4, 'after' => 4]);
    expect($calculator->calculateCapacityAtTimeArray($time, exceptReservations: [$reservation->id], exceptResources: [$resource->id]))
        ->toBe(['before' => 9, 'after' => 9]);
    expect($calculator->calculateCapacityAtTimeArray($time, excludedReservationResourceId: $excludedPivot->id))
        ->toBe(['before' => 6, 'after' => 6]);

    $range = new TimeRange('2026-01-15 12:00:00', '2026-01-15 19:00:00');
    $timeline = $calculator->getCapacityTimeline($range, exceptReservations: [$reservation->id], exceptResources: [$resource->id]);
    expect(array_keys($timeline))->toBe([
        $range->getStartTimestampMs(),
        $otherPivot->start_time->getTimestampMs(),
        $otherPivot->end_time->getTimestampMs(),
        $range->getEndTimestampMs(),
    ]);

    $timeline = $calculator->getCapacityTimeline($range, excludedReservationResourceId: $excludedPivot->id);
    expect(array_keys($timeline))->toBe([
        $range->getStartTimestampMs(),
        $retainedPivot->start_time->getTimestampMs(),
        $otherPivot->start_time->getTimestampMs(),
        $retainedPivot->end_time->getTimestampMs(),
        $otherPivot->end_time->getTimestampMs(),
        $range->getEndTimestampMs(),
    ]);
});

test('the timeline sums overlapping quantities and keeps reservation details in both modes', function (
    bool $ignoreTimeEndedActive,
    int $capacityDuringElapsedReservation,
    int $capacityDuringOverlap,
): void {
    $resource = makeCapacityTestResource();
    $calculator = new ResourceCapacityCalculator($resource);
    $range = new TimeRange('2026-01-15 08:00:00', '2026-01-15 13:00:00');
    $elapsedStart = Carbon::parse('2026-01-15 09:00:00');
    $elapsedEnd = Carbon::parse('2026-01-15 11:00:00');
    $otherStart = Carbon::parse('2026-01-15 10:00:00');
    $otherEnd = Carbon::parse('2026-01-15 12:00:00');

    $elapsed = Reservation::factory()->create([
        'name' => 'Workshop',
        'description' => 'Recording equipment',
        'start_time' => '2026-01-15 08:30:00',
        'end_time' => '2026-01-15 11:30:00',
    ]);
    $other = Reservation::factory()->create([
        'name' => 'Presentation',
        'description' => null,
        'start_time' => '2026-01-15 10:00:00',
        'end_time' => '2026-01-15 12:00:00',
    ]);
    reserveCapacityForTest($resource, '2026-01-15 09:00:00', '2026-01-15 11:00:00', 3, reservation: $elapsed);
    reserveCapacityForTest($resource, '2026-01-15 10:00:00', '2026-01-15 12:00:00', 4, reservation: $other);

    $elapsedPayload = [
        'id' => $elapsed->id,
        'name' => 'Workshop',
        'description' => 'Recording equipment',
        'start_time' => $elapsed->start_time,
        'end_time' => $elapsed->end_time,
    ];
    $otherPayload = [
        'id' => $other->id,
        'name' => 'Presentation',
        'description' => null,
        'start_time' => $other->start_time,
        'end_time' => $other->end_time,
    ];
    $expected = [
        $range->getStartTimestampMs() => ['before' => 10, 'after' => 10],
        $elapsedStart->getTimestampMs() => [
            'before' => 10, 'after' => $capacityDuringElapsedReservation, 'reservation' => $elapsedPayload, 'start' => true,
        ],
        $otherStart->getTimestampMs() => [
            'before' => $capacityDuringElapsedReservation, 'after' => $capacityDuringOverlap, 'reservation' => $otherPayload, 'start' => true,
        ],
        $elapsedEnd->getTimestampMs() => [
            'before' => $capacityDuringOverlap, 'after' => 6, 'reservation' => $elapsedPayload, 'end' => true,
        ],
        $otherEnd->getTimestampMs() => [
            'before' => 6, 'after' => 10, 'reservation' => $otherPayload, 'end' => true,
        ],
        $range->getEndTimestampMs() => ['before' => 10, 'after' => 10],
    ];

    $timeline = $calculator->getCapacityTimeline($range, ignoreTimeEndedActive: $ignoreTimeEndedActive);

    expect(array_keys($timeline))->toBe(array_keys($expected));
    expect($timeline)->toEqual($expected);
    expect($calculator->findLowestCapacity($timeline))->toBe($capacityDuringOverlap);
    expect($calculator->findTimeEndedActiveReservations($range)->modelKeys())->toBe([$elapsed->id]);
})->with([
    'strict' => [false, 7, 3],
    'flexible' => [true, 10, 6],
]);

test('the resource clamps its timeline to the requested range and takes the minimum after capacity', function (): void {
    $resource = makeCapacityTestResource();
    reserveCapacityForTest($resource, '2026-01-15 07:00:00', '2026-01-15 14:00:00', 2);
    reserveCapacityForTest($resource, '2026-01-15 07:30:00', '2026-01-15 08:00:00', 6);
    reserveCapacityForTest($resource, '2026-01-15 13:00:00', '2026-01-15 15:00:00', 1);
    $start = Carbon::parse('2026-01-15 08:00:00');
    $end = Carbon::parse('2026-01-15 13:00:00');

    $timeline = $resource->getCapacityAtDateTimeRange($start, $end);

    expect($timeline)->toBe([
        $start->getTimestampMs() => ['before' => 2, 'after' => 8],
        $end->getTimestampMs() => ['before' => 8, 'after' => 7],
    ]);
    expect($resource->lowestCapacityAtDateTimeRange($timeline))->toBe(7);
});
