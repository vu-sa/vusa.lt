<?php

use App\Models\Pivots\Dutiable;
use App\Support\Dutiables\ExOfficioSeatDates;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

function seatDatesRow(array $attributes = []): Dutiable
{
    return (new Dutiable)->forceFill([
        'id' => 'term', 'duty_id' => 'target', 'start_date' => '2026-01-01',
        'end_date' => null, 'ended_by_institution_deactivation' => false, ...$attributes,
    ])->setRelation('media', collect());
}

test('expected dates mirror the term including an open end', function (?string $end): void {
    $term = seatDatesRow(['end_date' => $end]);

    $result = ExOfficioSeatDates::forTarget($term, 'target', [], '2026-10-30');

    expect($result)->toBe([
        'dates' => ['start_date' => '2026-01-01', 'end_date' => $end],
        'seat' => null, 'duplicates' => [],
    ]);
})->with([null, '2026-12-31', '2026-06-30']);

test('a disjoint nonclosed seat is still the attached seat', function (): void {
    $term = seatDatesRow();
    $seat = seatDatesRow(['id' => 'seat', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);

    $result = ExOfficioSeatDates::forTarget($term, 'target', [$seat], '2026-10-30');

    expect($result['seat'])->toBe($seat)
        ->and($result['dates'])->toBe(['start_date' => '2026-01-01', 'end_date' => null])
        ->and($result['duplicates'])->toBeEmpty();
});

test('closed seats are immutable and a reopened seat resumes today', function (): void {
    $term = seatDatesRow();
    $closed = seatDatesRow(['id' => 'closed', 'end_date' => '2026-09-30', 'ended_by_institution_deactivation' => true]);
    $before = $closed->getAttributes();

    $result = ExOfficioSeatDates::forTarget($term, 'target', [$closed], '2026-10-30');

    expect($result)->toBe([
        'dates' => ['start_date' => '2026-10-30', 'end_date' => null], 'seat' => null, 'duplicates' => [],
    ])->and($closed->getAttributes())->toBe($before);
});

test('resume cannot overlap an inclusive closure end', function (): void {
    $closed = seatDatesRow(['end_date' => '2026-10-30', 'ended_by_institution_deactivation' => true]);

    expect(ExOfficioSeatDates::forTarget(seatDatesRow(), 'target', [$closed], '2026-10-30')['dates'])
        ->toBe(['start_date' => '2026-10-31', 'end_date' => null]);
});

test('an existing postclosure start stays fixed on later syncs', function (string $start): void {
    $closed = seatDatesRow(['id' => 'closed', 'end_date' => '2026-09-30', 'ended_by_institution_deactivation' => true]);
    $seat = seatDatesRow(['id' => 'seat', 'start_date' => $start]);

    $result = ExOfficioSeatDates::forTarget(seatDatesRow(), 'target', [$closed, $seat], '2026-10-30');

    expect($result['dates'])->toBe(['start_date' => $start, 'end_date' => null])
        ->and($result['seat'])->toBe($seat)->and($result['duplicates'])->toBeEmpty();
})->with(['2026-10-05', '2026-12-01']);

test('a term ending before resume has no live period', function (): void {
    $closed = seatDatesRow(['end_date' => '2026-09-30', 'ended_by_institution_deactivation' => true]);

    expect(ExOfficioSeatDates::forTarget(seatDatesRow(['end_date' => '2026-10-01']), 'target', [$closed], '2026-10-30')['dates'])
        ->toBeNull();
});

test('a term ended before its start is still mirrored', function (): void {
    $term = seatDatesRow(['start_date' => '2026-10-03', 'end_date' => '2026-10-02']);

    expect(ExOfficioSeatDates::forTarget($term, 'target', [seatDatesRow(['id' => 'seat'])], '2026-10-03')['dates'])
        ->toBe(['start_date' => '2026-10-03', 'end_date' => '2026-10-02']);
});

test('the last closure wins and closures on another target do not constrain dates', function (): void {
    $first = seatDatesRow(['id' => 'first', 'end_date' => '2026-08-31', 'ended_by_institution_deactivation' => true]);
    $last = seatDatesRow(['id' => 'last', 'end_date' => '2026-10-30', 'ended_by_institution_deactivation' => true]);
    $other = seatDatesRow(['id' => 'other', 'duty_id' => 'elsewhere', 'end_date' => '2027-01-31', 'ended_by_institution_deactivation' => true]);

    expect(ExOfficioSeatDates::forTarget(seatDatesRow(), 'target', [$first, $other, $last], '2026-10-30')['dates'])
        ->toBe(['start_date' => '2026-10-31', 'end_date' => null]);
});

test('longest overlap wins before details then id breaks ties', function (): void {
    $term = seatDatesRow(['end_date' => '2026-12-31']);
    $short = seatDatesRow(['id' => 'a', 'start_date' => '2026-02-01', 'end_date' => '2026-12-31', 'additional_email' => 'short@example.com']);
    $plain = seatDatesRow(['id' => 'b', 'end_date' => '2026-12-31']);
    $detailed = seatDatesRow(['id' => 'c', 'end_date' => '2026-12-31', 'additional_email' => 'kept@example.com']);
    $tied = seatDatesRow(['id' => 'd', 'end_date' => '2026-12-31', 'additional_email' => 'other@example.com']);

    $result = ExOfficioSeatDates::forTarget($term, 'target', [$short, $plain, $tied, $detailed], '2026-10-30');

    expect($result['seat'])->toBe($detailed)
        ->and(array_map(fn ($seat) => $seat->id, $result['duplicates']))->toBe(['d', 'b', 'a']);
});

test('loaded media counts as independent details without a query', function (): void {
    $plain = seatDatesRow(['id' => 'a']);
    $photo = seatDatesRow(['id' => 'b'])->setRelation('media', collect([new Media]));

    expect(ExOfficioSeatDates::forTarget(seatDatesRow(), 'target', [$plain, $photo], '2026-10-30')['seat'])->toBe($photo);
});
