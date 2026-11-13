<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeasonSettings extends Settings
{
    public bool $enabled = false;

    public string $starts_on = '12-01';

    public string $tree_locks_on = '12-25';

    public string $ends_on = '01-06';

    public int $ornament_adjust_minutes = 0;

    public int $ornament_cooldown_hours = 0;

    public static function group(): string
    {
        return 'season';
    }
}
