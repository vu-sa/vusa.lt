<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * Value object representing a time range with start and end times
 */
class TimeRange
{
    public readonly Carbon $start;

    public readonly Carbon $end;

    /**
     * Create a new TimeRange instance
     *
     * @param  Carbon|string|int  $start  Start time as Carbon, string, or timestamp
     * @param  Carbon|string|int  $end  End time as Carbon, string, or timestamp
     */
    public function __construct(Carbon|string|int $start, Carbon|string|int $end)
    {
        $this->start = $this->parseTime($start);
        $this->end = $this->parseTime($end);

        if ($this->start->isAfter($this->end)) {
            throw new \InvalidArgumentException('Start time must be before or equal to end time');
        }
    }

    /**
     * Parse time input into Carbon instance
     */
    private function parseTime(Carbon|string|int $time): Carbon
    {
        if ($time instanceof Carbon) {
            return $time->copy();
        }

        if (is_numeric($time)) {
            return Carbon::createFromTimestampMs($time);
        }

        return Carbon::parse($time);
    }

    /**
     * Check if a given time falls within this range
     */
    public function contains(Carbon $time): bool
    {
        return $time->isBetween($this->start, $this->end, true);
    }

    /**
     * Check if this range overlaps with another range
     */
    public function overlaps(TimeRange $other): bool
    {
        return $this->start->isBefore($other->end) && $this->end->isAfter($other->start);
    }

    /**
     * Get the duration of this time range in seconds
     */
    public function getDurationInSeconds(): int
    {
        return (int) $this->end->diffInSeconds($this->start);
    }

    /**
     * Get start time as timestamp in milliseconds
     */
    public function getStartTimestampMs(): int
    {
        return $this->start->getTimestampMs();
    }

    /**
     * Get end time as timestamp in milliseconds
     */
    public function getEndTimestampMs(): int
    {
        return $this->end->getTimestampMs();
    }
}
