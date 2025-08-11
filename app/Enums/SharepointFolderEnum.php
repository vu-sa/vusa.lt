<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self GENERAL()
 * @method static self PADALINIAI()
 * @method static self TYPES()
 * @method static self INSTITUTIONS()
 * @method static self MEETINGS()
 */
final class SharepointFolderEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'GENERAL' => 'General',
            'PADALINIAI' => 'Padaliniai',
            'TYPES' => 'Types',
            'INSTITUTIONS' => 'Institutions',
            'MEETINGS' => 'Meetings',
        ];
    }
}
