<?php

namespace App\Support;

use App\Settings\SeasonSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class Season
{
    private const string LAST_TREE_YEAR_KEY = 'season:last-tree-year:v1';

    /** Days the countdown on Pradžia shows before the season starts. */
    public const int COUNTDOWN_DAYS = 7;

    public function __construct(private readonly SeasonSettings $settings) {}

    /** New dates can turn a tree into last year's, or back: work it out again. */
    public static function forgetLastTreeYear(): void
    {
        Cache::forget(self::LAST_TREE_YEAR_KEY);
    }

    public function current(?CarbonInterface $now = null): ?ActiveSeason
    {
        if (! $this->settings->enabled) {
            return null;
        }

        $now = CarbonImmutable::instance($now ?? now())->setTimezone(config('app.timezone'));

        foreach ([$now->year, $now->year - 1] as $year) {
            $season = $this->forYear($year);

            if ($season->isActive($now)) {
                return $season;
            }
        }

        return null;
    }

    /**
     * When the next season starts, while that is at most a week away; null otherwise.
     *
     * @return array{startsAt: string}|null
     */
    public function countdown(?CarbonInterface $now = null): ?array
    {
        if (! $this->settings->enabled) {
            return null;
        }

        $now = CarbonImmutable::instance($now ?? now())->setTimezone(config('app.timezone'));

        foreach ([$now->year, $now->year + 1] as $year) {
            $startsAt = $this->forYear($year)->startsAt;

            if ($now->lessThan($startsAt) && $now->greaterThanOrEqualTo($startsAt->subDays(self::COUNTDOWN_DAYS))) {
                return ['startsAt' => $startsAt->toIso8601String()];
            }
        }

        return null;
    }

    public function forYear(int $year): ActiveSeason
    {
        return new ActiveSeason(
            key: 'advent',
            year: $year,
            startsAt: $this->date($this->settings->starts_on, $year),
            treeLocksAt: $this->date($this->settings->tree_locks_on, $year),
            endsAt: $this->date($this->settings->ends_on, $year)->addDay(),
        );
    }

    private function date(string $monthDay, int $year): CarbonImmutable
    {
        [$month, $day] = array_map(intval(...), explode('-', $monthDay));
        $dateYear = $monthDay < $this->settings->starts_on ? $year + 1 : $year;

        return CarbonImmutable::create($dateYear, $month, $day, 0, 0, 0, config('app.timezone'));
    }
}
