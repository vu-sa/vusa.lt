<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self LT()
 * @method static self EN()
 */
final class LocaleEnum extends Enum
{
    use hasCamelCaseLabels;
}
