<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self CREATE()
 * @method static self READ()
 * @method static self UPDATE()
 * @method static self DELETE()
 * @method static self FORCE_DELETE()
 */
final class CRUDEnum extends Enum
{
    use HasCamelCaseLabels;
}
