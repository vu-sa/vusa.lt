<?php

namespace App\Settings;

use App\Enums\SystemDutyType;
use Spatie\LaravelSettings\Settings;

/**
 * Which duty type plays each {@see SystemDutyType} part on the public contact pages.
 */
class DutyTypeSettings extends Settings
{
    public ?int $coordinator_type_id = null;

    public ?int $curator_type_id = null;

    public ?int $mentor_type_id = null;

    public static function group(): string
    {
        return 'duty_types';
    }
}
