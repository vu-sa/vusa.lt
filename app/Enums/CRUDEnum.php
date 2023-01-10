<?php
namespace App\Enums;

use App\Enums\Traits\hasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 * @method static self CREATE()
 * @method static self READ()
 * @method static self UPDATE()
 * @method static self DELETE()
 */
final class CRUDEnum extends Enum {
    use hasCamelCaseLabels;
}