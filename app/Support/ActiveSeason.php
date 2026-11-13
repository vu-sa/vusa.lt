<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

readonly class ActiveSeason
{
    /** How steeply the snow piles up toward the lock: ~18 % at half time, ~37 % a week before, then the rest. */
    private const float SNOW_CURVE = 3.0;

    public function __construct(
        public string $key,
        public int $year,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $treeLocksAt,
        public CarbonImmutable $endsAt,
    ) {}

    public function isActive(CarbonInterface $now): bool
    {
        return $now->greaterThanOrEqualTo($this->startsAt) && $now->lessThan($this->endsAt);
    }

    public function treeLocked(CarbonInterface $now): bool
    {
        return $now->greaterThanOrEqualTo($this->treeLocksAt);
    }

    /**
     * Snow settled on the tree, 0 when the season starts and 1 at the lock: little at first, most of
     * it in the last week, so the tree visibly fills up as the lock nears.
     */
    public function snowCover(CarbonInterface $now): float
    {
        $span = $this->startsAt->diffInSeconds($this->treeLocksAt);

        if ($span <= 0) {
            return 1.0;
        }

        $progress = max(0, min(1, $this->startsAt->diffInSeconds($now, false) / $span));

        return round((exp(self::SNOW_CURVE * $progress) - 1) / (exp(self::SNOW_CURVE) - 1), 3);
    }

    /** @return array{key: string, year: int, treeLocked: bool} */
    public function toArray(CarbonInterface $now): array
    {
        return [
            'key' => $this->key,
            'year' => $this->year,
            'treeLocked' => $this->treeLocked($now),
        ];
    }
}
