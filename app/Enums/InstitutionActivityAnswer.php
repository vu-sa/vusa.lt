<?php

namespace App\Enums;

enum InstitutionActivityAnswer: string
{
    case Met = 'met';
    case NotMet = 'not_met';
    case NotMine = 'not_mine';
    case Complete = 'complete';

    /** Met and not met record the institution's activity; "not mine" only flags stale data. */
    public function recordsActivity(): bool
    {
        return $this !== self::NotMine;
    }
}
