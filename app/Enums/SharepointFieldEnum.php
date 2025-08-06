<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self PADALINYS()
 * @method static self TITLE()
 * @method static self DATE()
 * @method static self EFFECTIVE_DATE()
 * @method static self EXPIRATION_DATE()
 * @method static self LANGUAGE()
 * @method static self TURINYS()
 * @method static self SUMMARY()
 */
final class SharepointFieldEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'PADALINYS' => 'Padalinys',
            'TITLE' => 'Title',
            'DATE' => 'Date',
            'EFFECTIVE_DATE' => 'Effective_x0020_Date',
            'EXPIRATION_DATE' => 'Expiration_x0020_Date0',
            'LANGUAGE' => 'Language',
            'TURINYS' => 'Turinys',
            'SUMMARY' => 'Summary',
        ];
    }
}
