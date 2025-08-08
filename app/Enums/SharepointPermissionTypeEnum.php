<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self VIEW()
 * @method static self EDIT()
 * @method static self OWNER()
 */
final class SharepointPermissionTypeEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'VIEW' => 'view',
            'EDIT' => 'edit',
            'OWNER' => 'owner',
        ];
    }
}
