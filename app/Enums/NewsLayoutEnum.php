<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self MODERN()
 * @method static self CLASSIC()
 * @method static self IMMERSIVE()
 * @method static self HEADLINE()
 */
final class NewsLayoutEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'MODERN' => 'modern',
            'CLASSIC' => 'classic',
            'IMMERSIVE' => 'immersive',
            'HEADLINE' => 'headline',
        ];
    }
}
