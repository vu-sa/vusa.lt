<?php

namespace Tests\Feature\Admin\Core;

use App\Services\AcademicCalendarService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class LegacyAcademicCalendarService extends AcademicCalendarService
{
    public function effectiveDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        [$start, $end] = $this->orderedDays($from, $to);

        $totalDays = (int) $start->diffInDays($end);

        return max(0, $totalDays - $this->vacationDaysBetween($start, $end));
    }

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
