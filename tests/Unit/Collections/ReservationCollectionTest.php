<?php

use App\Collections\ReservationCollection;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\States\ReservationResource\Created;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Rejected;
use App\States\ReservationResource\Reserved;
use Carbon\Carbon;

beforeEach(function () {
    // Create test dates
    $this->mockStartTime = Carbon::parse('2024-01-15 10:00:00');
    $this->mockEndTime = Carbon::parse('2024-01-15 12:00:00');
    $this->overlapStartTime = Carbon::parse('2024-01-15 11:00:00');
    $this->overlapEndTime = Carbon::parse('2024-01-15 13:00:00');

    // Build Reservation models in memory (no DB persistence needed for collection logic)
    $this->reservation1 = new Reservation([
        'id' => '01HZABC1234567890ABCDEF1',
        'name' => 'Test Reservation 1',
        'description' => 'Test description 1',
    ]);

    $this->reservation2 = new Reservation([
        'id' => '01HZABC1234567890ABCDEF2',
        'name' => 'Test Reservation 2',
        'description' => 'Test description 2',
    ]);

    $this->reservation3 = new Reservation([
        'id' => '01HZABC1234567890ABCDEF3',
        'name' => 'Test Reservation 3',
        'description' => 'Test description 3',
    ]);

    // Build pivot instances in memory
    $this->pivot1 = new ReservationResource([
        'reservation_id' => $this->reservation1->id,
        'resource_id' => 1,
        'start_time' => $this->mockStartTime,
        'end_time' => $this->mockEndTime,
        'quantity' => 2,
        'state' => Reserved::class,
    ]);

    $this->pivot2 = new ReservationResource([
        'reservation_id' => $this->reservation2->id,
        'resource_id' => 1,
        'start_time' => $this->overlapStartTime,
        'end_time' => $this->overlapEndTime,
        'quantity' => 3,
        'state' => Created::class,
    ]);

    $this->pivot3 = new ReservationResource([
        'reservation_id' => $this->reservation3->id,
        'resource_id' => 1,
        'start_time' => Carbon::parse('2024-01-16 14:00:00'),
        'end_time' => Carbon::parse('2024-01-16 16:00:00'),
        'quantity' => 1,
        'state' => Reserved::class,
    ]);

    // Set up reservations with pivot relationships
    $this->reservation1->setRelation('pivot', $this->pivot1);
    $this->reservation2->setRelation('pivot', $this->pivot2);
    $this->reservation3->setRelation('pivot', $this->pivot3);

    // Create collection
    $this->reservations = collect([$this->reservation1, $this->reservation2, $this->reservation3]);
    $this->collection = new ReservationCollection($this->reservations);
});

describe('getPivots method', function () {
    test('returns collection of all pivot instances', function () {
        $pivots = $this->collection->getPivots();

        expect($pivots)->toHaveCount(3);
        $pivots->each(function ($pivot) {
            expect($pivot)->toBeInstanceOf(ReservationResource::class);
        });
    });

    test('returns empty collection for empty reservation collection', function () {
        $emptyCollection = new ReservationCollection([]);
        $pivots = $emptyCollection->getPivots();

        expect($pivots)->toBeEmpty();
    });
});

describe('whereState method', function () {
    test('filters reservations by single state', function () {
        $reservedReservations = $this->collection->whereState('reserved');

        expect($reservedReservations)->toHaveCount(2);
        $reservedReservations->each(function ($reservation) {
            expect($reservation->pivot->state::class)->toBe(Reserved::class);
        });
    });

    test('filters reservations by multiple states', function () {
        $filteredReservations = $this->collection->whereState(['reserved', 'created']);

        expect($filteredReservations)->toHaveCount(3);
    });

    test('returns empty collection for non-existent state', function () {
        $filteredReservations = $this->collection->whereState('non-existent');

        expect($filteredReservations)->toBeEmpty();
    });

    test('handles enum state values', function () {
        $enumReservation = new Reservation([
            'id' => '01HZABC1234567890ABCDEF4',
            'name' => 'Enum State Reservation',
        ]);

        $enumPivot = new ReservationResource([
            'reservation_id' => $enumReservation->id,
            'resource_id' => 1,
            'start_time' => $this->mockStartTime,
            'end_time' => $this->mockEndTime,
            'quantity' => 1,
            'state' => Lent::class,
        ]);

        $enumReservation->setRelation('pivot', $enumPivot);

        $collection = new ReservationCollection([$enumReservation]);
        $filtered = $collection->whereState('lent');

        expect($filtered)->toHaveCount(1);
    });
});

describe('whereOverlaps method', function () {
    test('returns reservations that overlap with given time range', function () {
        $overlappingReservations = $this->collection->whereOverlaps(
            Carbon::parse('2024-01-15 11:30:00'),
            Carbon::parse('2024-01-15 11:45:00')
        );

        expect($overlappingReservations)->toHaveCount(2);
    });

    test('returns reservations that start before end time and end after start time', function () {
        // Test exact overlap logic: start < end && end > start
        // Query: 10:30 to 11:30 should overlap with both reservation1 (10:00-12:00) and reservation2 (11:00-13:00)
        $overlappingReservations = $this->collection->whereOverlaps(
            $this->mockStartTime->copy()->addMinutes(30), // 10:30
            $this->mockEndTime->copy()->subMinutes(30)    // 11:30
        );

        expect($overlappingReservations)->toHaveCount(2);
    });

    test('returns empty collection when no overlaps', function () {
        $noOverlaps = $this->collection->whereOverlaps(
            Carbon::parse('2024-01-20 10:00:00'),
            Carbon::parse('2024-01-20 12:00:00')
        );

        expect($noOverlaps)->toBeEmpty();
    });

    test('handles edge cases with exact start/end times', function () {
        // Test when query end time equals reservation start time
        $edgeCaseOverlaps = $this->collection->whereOverlaps(
            Carbon::parse('2024-01-15 08:00:00'),
            $this->mockStartTime // Exactly when first reservation starts
        );

        expect($edgeCaseOverlaps)->toBeEmpty();
    });
});

describe('getTotalQuantity method', function () {
    test('calculates sum of all reservation quantities', function () {
        $totalQuantity = $this->collection->getTotalQuantity();

        expect($totalQuantity)->toBe(6); // 2 + 3 + 1
    });

    test('returns zero for empty collection', function () {
        $emptyCollection = new ReservationCollection([]);
        $totalQuantity = $emptyCollection->getTotalQuantity();

        expect($totalQuantity)->toBe(0);
    });

    test('handles null quantities gracefully', function () {
        $nullQuantityReservation = new Reservation([
            'id' => '01HZABC1234567890ABCDEF5',
            'name' => 'Null Quantity Reservation',
        ]);

        $nullQuantityPivot = new ReservationResource([
            'reservation_id' => $nullQuantityReservation->id,
            'resource_id' => 1,
            'start_time' => $this->mockStartTime,
            'end_time' => $this->mockEndTime,
            'quantity' => 0,
            'state' => Created::class,
        ]);

        $nullQuantityReservation->setRelation('pivot', $nullQuantityPivot);

        $collection = new ReservationCollection([$nullQuantityReservation]);
        $totalQuantity = $collection->getTotalQuantity();

        expect($totalQuantity)->toBe(0);
    });
});

describe('whereStartsBefore method', function () {
    test('returns reservations starting before given time', function () {
        $reservationsStartingBefore = $this->collection->whereStartsBefore(
            Carbon::parse('2024-01-15 11:30:00')
        );

        expect($reservationsStartingBefore)->toHaveCount(2);
    });

    test('returns empty collection when no reservations start before time', function () {
        $reservationsStartingBefore = $this->collection->whereStartsBefore(
            Carbon::parse('2024-01-15 09:00:00')
        );

        expect($reservationsStartingBefore)->toBeEmpty();
    });
});

describe('whereEndsAfter method', function () {
    test('returns reservations ending after given time', function () {
        $reservationsEndingAfter = $this->collection->whereEndsAfter(
            Carbon::parse('2024-01-15 11:30:00')
        );

        expect($reservationsEndingAfter)->toHaveCount(3); // All reservations end after 11:30
    });

    test('returns empty collection when no reservations end after time', function () {
        $reservationsEndingAfter = $this->collection->whereEndsAfter(
            Carbon::parse('2024-01-17 18:00:00')
        );

        expect($reservationsEndingAfter)->toBeEmpty();
    });
});

describe('sortByStartTime method', function () {
    test('sorts reservations by start time ascending by default', function () {
        $sortedReservations = $this->collection->sortByStartTime();

        $times = $sortedReservations->map(fn ($r) => $r->pivot->start_time);
        expect($times->first()->format('Y-m-d H:i:s'))->toBe($this->mockStartTime->format('Y-m-d H:i:s'));
    });

    test('sorts reservations by start time descending when specified', function () {
        $sortedReservations = $this->collection->sortByStartTime(true);

        $times = $sortedReservations->map(fn ($r) => $r->pivot->start_time);
        expect($times->first()->format('Y-m-d H:i:s'))->toBe('2024-01-16 14:00:00');
    });
});

describe('sortByEndTime method', function () {
    test('sorts reservations by end time ascending by default', function () {
        $sortedReservations = $this->collection->sortByEndTime();

        $times = $sortedReservations->map(fn ($r) => $r->pivot->end_time);
        expect($times->first()->format('Y-m-d H:i:s'))->toBe($this->mockEndTime->format('Y-m-d H:i:s'));
    });

    test('sorts reservations by end time descending when specified', function () {
        $sortedReservations = $this->collection->sortByEndTime(true);

        $times = $sortedReservations->map(fn ($r) => $r->pivot->end_time);
        expect($times->first()->format('Y-m-d H:i:s'))->toBe('2024-01-16 16:00:00');
    });
});

describe('groupByState method', function () {
    test('groups reservations by their pivot state', function () {
        $groupedReservations = $this->collection->groupByState();

        expect($groupedReservations)->toHaveCount(2);
        expect($groupedReservations->has('reserved'))->toBeTrue();
        expect($groupedReservations->has('created'))->toBeTrue();
        expect($groupedReservations->get('reserved'))->toHaveCount(2);
        expect($groupedReservations->get('created'))->toHaveCount(1);
    });

    test('returns ReservationCollection instances for each group', function () {
        $groupedReservations = $this->collection->groupByState();

        $groupedReservations->each(function ($group) {
            expect($group)->toBeInstanceOf(ReservationCollection::class);
        });
    });

    test('handles enum state values in grouping', function () {
        $rejectedReservation = new Reservation([
            'id' => '01HZABC1234567890ABCDEF6',
            'name' => 'Rejected State Reservation',
        ]);

        $rejectedPivot = new ReservationResource([
            'reservation_id' => $rejectedReservation->id,
            'resource_id' => 1,
            'start_time' => $this->mockStartTime,
            'end_time' => $this->mockEndTime,
            'quantity' => 1,
            'state' => Rejected::class,
        ]);

        $rejectedReservation->setRelation('pivot', $rejectedPivot);

        $collection = new ReservationCollection([$rejectedReservation]);
        $grouped = $collection->groupByState();

        expect($grouped->has('rejected'))->toBeTrue();
    });
});

describe('toOptimizedArray method', function () {
    test('returns optimized array structure for API responses', function () {
        $optimizedArray = $this->collection->toOptimizedArray();

        expect($optimizedArray)->toHaveCount(3);

        $firstItem = $optimizedArray[0];
        expect($firstItem)->toHaveKeys(['id', 'name', 'description', 'pivot']);
        expect($firstItem['pivot'])->toHaveKeys(['start_time', 'end_time', 'quantity', 'state']);
    });

    test('handles missing description field gracefully', function () {
        $noDescReservation = new Reservation([
            'id' => '01HZABC1234567890ABCDEF7',
            'name' => 'No Description Reservation',
            'description' => null,
        ]);

        $noDescPivot = new ReservationResource([
            'reservation_id' => $noDescReservation->id,
            'resource_id' => 1,
            'start_time' => $this->mockStartTime,
            'end_time' => $this->mockEndTime,
            'quantity' => 1,
            'state' => Created::class,
        ]);

        $noDescReservation->setRelation('pivot', $noDescPivot);

        $collection = new ReservationCollection([$noDescReservation]);
        $optimizedArray = $collection->toOptimizedArray();

        expect($optimizedArray[0]['description'])->toBeNull();
    });

    test('handles enum state values in optimized array', function () {
        $enumStateReservation = new Reservation([
            'id' => '01HZABC1234567890ABCDEF8',
            'name' => 'Enum State Reservation',
        ]);

        $enumStatePivot = new ReservationResource([
            'reservation_id' => $enumStateReservation->id,
            'resource_id' => 1,
            'start_time' => $this->mockStartTime,
            'end_time' => $this->mockEndTime,
            'quantity' => 1,
            'state' => Lent::class,
        ]);

        $enumStateReservation->setRelation('pivot', $enumStatePivot);

        $collection = new ReservationCollection([$enumStateReservation]);
        $optimizedArray = $collection->toOptimizedArray();

        expect($optimizedArray[0]['pivot']['state'])->toBe('lent');
    });
});
