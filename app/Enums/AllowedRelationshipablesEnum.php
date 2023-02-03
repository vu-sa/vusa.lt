<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 * @method static self INSTITUTION()
 * @method static self TYPE()
 */
final class AllowedRelationshipablesEnum extends Enum {
    use hasCamelCaseLabels;
}