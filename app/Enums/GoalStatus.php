<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    case NotAchieved = 'not_achieved';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planuojamas'),
            self::InProgress => __('Vykdomas'),
            self::Achieved => __('Pasiektas'),
            self::NotAchieved => __('Nepasiektas'),
            self::Dropped => __('Atsisakyta'),
        };
    }

    /** The year is over for this goal, so its evaluation is what readers want. */
    public function isClosed(): bool
    {
        return in_array($this, [self::Achieved, self::NotAchieved, self::Dropped], true);
    }
}
