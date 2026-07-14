<?php

use App\Services\AcademicCalendarService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->calendar = new AcademicCalendarService;
});

describe('easterSunday', function () {
    test('calculates known Easter dates', function (int $year, string $expected) {
        expect(AcademicCalendarService::easterSunday($year)->toDateString())->toBe($expected);
    })->with([
        [2024, '2024-03-31'],
        [2025, '2025-04-20'],
        [2026, '2026-04-05'],
        [2027, '2027-03-28'],
    ]);
});

describe('isVacationDate', function () {
    test('recognizes vacation days', function (string $date) {
        expect($this->calendar->isVacationDate(CarbonImmutable::parse($date)))->toBeTrue();
    })->with([
        '2025-07-15', // summer
        '2025-08-31', // last day of summer vacation
        '2025-12-27', // winter
        '2026-01-01', // last day of winter vacation, previous year's period
        '2026-02-01', // late January vacation
        '2026-04-06', // Easter Monday (Easter 2026: April 5)
    ]);

    test('recognizes working days', function (string $date) {
        expect($this->calendar->isVacationDate(CarbonImmutable::parse($date)))->toBeFalse();
    })->with([
        '2025-10-15', // plain autumn day
        '2025-09-01', // day after summer vacation ends
        '2025-12-23', // day before winter vacation starts
        '2026-02-05', // day after late January vacation ends
    ]);
});

describe('effectiveDaysBetween', function () {
    test('equals the calendar difference outside vacations', function () {
        $days = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-10-01'),
            CarbonImmutable::parse('2025-10-31'),
        );

        expect($days)->toBe(30);
    });

    test('excludes a fully contained vacation period', function () {
        // July 1 - August 31 (62 days) sits entirely inside this range.
        $days = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-06-01'),
            CarbonImmutable::parse('2025-09-30'),
        );

        expect($days)->toBe(121 - 62);
    });

    test('returns zero when the whole range is vacation', function () {
        $days = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-07-05'),
            CarbonImmutable::parse('2025-08-20'),
        );

        expect($days)->toBe(0);
    });

    test('subtracts only the overlapping part of a vacation', function () {
        // June 25 -> July 10: 15 calendar days, of which July 1-9 (9 days) are vacation.
        $days = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-06-25'),
            CarbonImmutable::parse('2025-07-10'),
        );

        expect($days)->toBe(6);
    });

    test('is order-insensitive and never negative', function () {
        $forwards = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-06-01'),
            CarbonImmutable::parse('2025-09-30'),
        );

        $backwards = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-09-30'),
            CarbonImmutable::parse('2025-06-01'),
        );

        expect($backwards)->toBe($forwards)->toBeGreaterThan(0);
    });

    test('does not subtract overlapping vacation days twice', function () {
        // Spans winter (Dec 24 - Jan 1) and late January vacation of the next year.
        $days = $this->calendar->effectiveDaysBetween(
            CarbonImmutable::parse('2025-12-01'),
            CarbonImmutable::parse('2026-03-01'),
        );

        $calendarDays = CarbonImmutable::parse('2025-12-01')->diffInDays(CarbonImmutable::parse('2026-03-01'));

        expect($days)->toBeLessThan((int) $calendarDays)->toBeGreaterThan(0);
    });
});

describe('addEffectiveDays', function () {
    test('skips over a vacation period', function () {
        // 7 effective days from June 28: June 29, 30 count, July is vacation,
        // so the remaining 5 days land in September.
        $due = $this->calendar->addEffectiveDays(CarbonImmutable::parse('2025-06-28'), 7);

        expect($due->toDateString())->toBe('2025-09-05');
    });

    test('adds plain days outside vacations', function () {
        $due = $this->calendar->addEffectiveDays(CarbonImmutable::parse('2025-10-01'), 7);

        expect($due->toDateString())->toBe('2025-10-08');
    });

    test('never returns a vacation day', function () {
        $due = $this->calendar->addEffectiveDays(CarbonImmutable::parse('2025-12-20'), 5);

        expect($this->calendar->isVacationDate($due))->toBeFalse();
    });
});
