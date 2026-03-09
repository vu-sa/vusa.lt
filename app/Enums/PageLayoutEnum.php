<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self DEFAULT()
 * @method static self WIDE()
 * @method static self FOCUSED()
 */
final class PageLayoutEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'DEFAULT' => 'default',
            'WIDE' => 'wide',
            'FOCUSED' => 'focused',
        ];
    }
}
