<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The window (22:00–07:00, app timezone) in which push and digest emails are held back.
 */
final class QuietHours
{
    public const START_HOUR = 22;

    public const END_HOUR = 7;

    public static function isQuiet(CarbonInterface $at): bool
    {
        $hour = self::local($at)->hour;

        return $hour >= self::START_HOUR || $hour < self::END_HOUR;
    }

    /**
     * The next 07:00 at or after $at (or $at itself when it is not quiet).
     */
    public static function nextEnd(CarbonInterface $at): CarbonImmutable
    {
        $local = self::local($at);

        if (! self::isQuiet($at)) {
            return $local;
        }

        $end = $local->setTime(self::END_HOUR, 0);

        return $local->hour >= self::START_HOUR ? $end->addDay() : $end;
    }

    private static function local(CarbonInterface $at): CarbonImmutable
    {
        return CarbonImmutable::instance($at)->setTimezone(config('app.timezone'));
    }
}
