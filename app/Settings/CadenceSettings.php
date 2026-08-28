<?php

namespace App\Settings;

use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

/**
 * Organisation-wide term boundaries.
 *
 * Kept separate from {@see AtstovavimasSettings} because that class is read on hot
 * permission paths and is cached per user — appending cadence fields would make every
 * one of those reads deserialize calendar config it never uses.
 */
class CadenceSettings extends Settings
{
    /** `MM-DD`. 348 dutiables already start on 1 July — the modal value. */
    public string $default_start_month_day = '07-01';

    /**
     * `MM-DD`, deliberately the day *before* the start rather than 07-01.
     *
     * 07-01 is today's most common end month-day (592 rows), but that is precisely the
     * "one term ends the day the next begins" overlap the editor exists to fix
     * (the `boundary_shared` diagnostic). The default must not perpetuate it.
     */
    public string $default_end_month_day = '06-30';

    public static function group(): string
    {
        return 'cadence';
    }

    /**
     * The concrete window for a term beginning in the given year. Only used to prefill
     * a new cadence row — nothing materialises terms on its own.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function windowFor(int $startYear): array
    {
        $start = $this->dateFor($startYear, $this->default_start_month_day);
        $end = $this->dateFor($startYear, $this->default_end_month_day);

        // A term that ends on or before it starts runs into the following year
        // (07-01 → 06-30). One that does not is a same-year term (01-01 → 12-31).
        if ($end->lessThanOrEqualTo($start)) {
            $end = $this->dateFor($startYear + 1, $this->default_end_month_day);
        }

        return [$start, $end];
    }

    private function dateFor(int $year, string $monthDay): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', sprintf('%d-%s', $year, $monthDay))->startOfDay();
    }
}
