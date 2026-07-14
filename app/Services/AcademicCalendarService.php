<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Academic vacation calendar for Lithuanian universities.
 *
 * Institutions are not expected to meet during vacations, so these days are excluded
 * when measuring how long an institution has been inactive and when setting task due dates.
 *
 * The same periods are shaded in the meetings Gantt chart by
 * resources/js/Pages/Admin/Dashboard/Components/vacationConfig.ts — keep both in sync.
 *
 * @phpstan-type VacationPeriod array{start: CarbonImmutable, end: CarbonImmutable, type: string}
 */
class AcademicCalendarService
{
    /** @var array<int, list<VacationPeriod>> */
    private array $periodsByYear = [];

    /**
     * Vacation periods beginning in the given year. Period bounds are inclusive.
     *
     * @return list<VacationPeriod>
     */
    public function vacationPeriodsForYear(int $year): array
    {
        return $this->periodsByYear[$year] ??= $this->buildPeriodsForYear($year);
    }

    /**
     * Vacation periods overlapping the given range.
     *
     * @return list<VacationPeriod>
     */
    public function vacationPeriods(CarbonInterface $from, CarbonInterface $to): array
    {
        [$start, $end] = $this->orderedDays($from, $to);

        $periods = [];

        // Winter vacation starts in December and ends on January 1st, so the previous
        // year can still contribute a period overlapping the start of the range.
        for ($year = $start->year - 1; $year <= $end->year; $year++) {
            foreach ($this->vacationPeriodsForYear($year) as $period) {
                if ($period['start']->lte($end) && $period['end']->gte($start)) {
                    $periods[] = $period;
                }
            }
        }

        return $periods;
    }

    public function isVacationDate(CarbonInterface $date): bool
    {
        $day = CarbonImmutable::instance($date)->startOfDay();

        foreach ($this->vacationPeriodsForYear($day->year - 1) as $period) {
            if ($day->betweenIncluded($period['start'], $period['end'])) {
                return true;
            }
        }

        foreach ($this->vacationPeriodsForYear($day->year) as $period) {
            if ($day->betweenIncluded($period['start'], $period['end'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Days between two dates, excluding days that fall inside a vacation period.
     *
     * Order-insensitive and never negative.
     */
    public function effectiveDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        [$start, $end] = $this->orderedDays($from, $to);

        $totalDays = (int) $start->diffInDays($end);

        return max(0, $totalDays - $this->vacationDaysBetween($start, $end));
    }

    /**
     * Add days to a date, skipping vacation days, so that the result lies
     * $days effective days ahead of $from.
     */
    public function addEffectiveDays(CarbonInterface $from, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($from)->startOfDay();

        for ($remaining = $days; $remaining > 0;) {
            $date = $date->addDay();

            if (! $this->isVacationDate($date)) {
                $remaining--;
            }
        }

        return $date;
    }

    /**
     * Easter Sunday for a given year (Anonymous Gregorian algorithm).
     *
     * @see https://en.wikipedia.org/wiki/Computus
     */
    public static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    /**
     * @return list<VacationPeriod>
     */
    private function buildPeriodsForYear(int $year): array
    {
        $easter = self::easterSunday($year);
        $lastDayOfJanuary = CarbonImmutable::create($year, 1, 31)->startOfDay();

        return [
            [
                'start' => CarbonImmutable::create($year, 7, 1)->startOfDay(),
                'end' => CarbonImmutable::create($year, 8, 31)->startOfDay(),
                'type' => 'summer',
            ],
            [
                'start' => CarbonImmutable::create($year, 12, 24)->startOfDay(),
                'end' => CarbonImmutable::create($year + 1, 1, 1)->startOfDay(),
                'type' => 'winter',
            ],
            [
                // Last Monday of January until February 4th.
                'start' => $lastDayOfJanuary->subDays(($lastDayOfJanuary->dayOfWeek + 6) % 7),
                'end' => CarbonImmutable::create($year, 2, 4)->startOfDay(),
                'type' => 'winter',
            ],
            [
                'start' => $easter->subDays(7),
                'end' => $easter->addDays(2),
                'type' => 'easter',
            ],
        ];
    }

    /**
     * Number of vacation days in the half-open range [$start, $end), matching how
     * effectiveDaysBetween() counts total days. Overlapping periods are merged so
     * shared days are not subtracted twice.
     */
    private function vacationDaysBetween(CarbonImmutable $start, CarbonImmutable $end): int
    {
        /** @var list<array{0: CarbonImmutable, 1: CarbonImmutable}> $intervals */
        $intervals = [];

        foreach ($this->vacationPeriods($start, $end) as $period) {
            $from = $period['start']->max($start);
            // Period bounds are inclusive, the range is half-open.
            $until = $period['end']->addDay()->min($end);

            if ($from->lt($until)) {
                $intervals[] = [$from, $until];
            }
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $days = 0;
        $mergedUntil = null;

        foreach ($intervals as [$from, $until]) {
            if ($mergedUntil !== null && $from->lt($mergedUntil)) {
                $from = $mergedUntil;
            }

            if ($from->lt($until)) {
                $days += (int) $from->diffInDays($until);
                $mergedUntil = $until;
            }
        }

        return $days;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function orderedDays(CarbonInterface $from, CarbonInterface $to): array
    {
        $first = CarbonImmutable::instance($from)->startOfDay();
        $second = CarbonImmutable::instance($to)->startOfDay();

        return $first->lte($second) ? [$first, $second] : [$second, $first];
    }
}
