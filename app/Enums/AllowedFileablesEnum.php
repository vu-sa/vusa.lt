<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 * @method static self DOING()
 * @method static self DUTY()
 * @method static self GOAL()
 * @method static self INSTITUTION()
 * @method static self MEETING()
 * @method static self TYPE()
 * @method static self USER()
 */
final class AllowedFileablesEnum extends Enum {
    use hasCamelCaseLabels;
}