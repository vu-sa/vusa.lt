<?php

namespace App\Support\Dutiables;

use App\Models\Pivots\Dutiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** @phpstan-type Period array{start_date: string, end_date: string|null} */
class DutiablePeriods
{
    /** @return Period */
    public static function dates(Dutiable $row): array
    {
        return ['start_date' => $row->start_date->toDateString(), 'end_date' => $row->end_date?->toDateString()];
    }

    public static function current(Dutiable $row): bool
    {
        return $row->start_date->lte(today()) && ($row->end_date === null || $row->end_date->gte(today()));
    }

    /** A term grants its permissions from the moment it exists, before it starts; only its end takes them away. */
    public static function grantsAccess(Dutiable $row): bool
    {
        return $row->end_date === null || $row->end_date->gte(today());
    }

    public static function overlapDays(Dutiable $a, Dutiable $b): int
    {
        $start = max($a->start_date->toDateString(), $b->start_date->toDateString());
        $end = min($a->end_date?->toDateString() ?? '9999-12-31', $b->end_date?->toDateString() ?? '9999-12-31');

        return $end < $start ? 0 : (int) Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
    }

    /** @return list<Period> */
    public static function remainders(Dutiable $row, Collection $sources): array
    {
        $periods = [self::dates($row)];
        foreach ($sources->sortBy('start_date') as $source) {
            $next = [];
            foreach ($periods as $period) {
                $next = array_merge($next, self::subtract($period, $source));
            }
            $periods = $next;
        }

        return $periods;
    }

    /**
     * @param  Period  $period
     * @return list<Period>
     */
    private static function subtract(array $period, Dutiable $source): array
    {
        $start = $source->start_date->toDateString();
        $end = $source->end_date?->toDateString();
        if (($period['end_date'] !== null && $period['end_date'] < $start)
            || ($end !== null && $end < $period['start_date'])) {
            return [$period];
        }
        $remaining = [];
        if ($period['start_date'] < $start) {
            $remaining[] = [
                'start_date' => $period['start_date'],
                'end_date' => Carbon::parse($start)->subDay()->toDateString(),
            ];
        }
        if ($end !== null && ($period['end_date'] === null || $end < $period['end_date'])) {
            $remaining[] = [
                'start_date' => Carbon::parse($end)->addDay()->toDateString(),
                'end_date' => $period['end_date'],
            ];
        }

        return $remaining;
    }
}
