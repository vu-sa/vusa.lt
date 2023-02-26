<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self OWN()
 * @method static self PADALINYS()
 * @method static self ALL()
 */
class PermissionScopeEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'OWN' => 'own',
            'PADALINYS' => 'padalinys',
            'ALL' => '*',
        ];
    }
}
