<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self ANONYMOUS()
 * @method static self ORGANIZATION()
 * @method static self USERS()
 */
final class SharepointScopeEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'ANONYMOUS' => 'anonymous',
            'ORGANIZATION' => 'organization',
            'USERS' => 'users',
        ];
    }
}
