<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How recently someone used Mano VU SA, in bands: the exact `users.last_action` is never shown to others.
 */
enum LoginRecency: string
{
    case Week = 'week';
    case Month = 'month';
    case Stale = 'stale';
    case Never = 'never';

    public static function fromLastAction(?CarbonInterface $lastAction, ?CarbonInterface $now = null): self
    {
        $now ??= now();

        return match (true) {
            $lastAction === null => self::Never,
            $lastAction->gte($now->copy()->subDays(7)) => self::Week,
            $lastAction->gte($now->copy()->subDays(30)) => self::Month,
            default => self::Stale,
        };
    }
}
