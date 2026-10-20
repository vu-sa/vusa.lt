<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RepMetricSettings extends Settings
{
    public ?string $baseline_cadence_id = null;

    public static function group(): string
    {
        return 'rep_metrics';
    }
}
