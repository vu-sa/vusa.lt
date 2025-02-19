<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self IMAGE_GRID()
 * @method static self SHADCN_ACCORDION()
 * @method static self SHADCN_CARD()
 * @method static self TIPTAP()
 */
final class ContentPartEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'IMAGE_GRID' => 'image-grid',
            'SHADCN_ACCORDION' => 'shadcn-accordion',
            'SHADCN_CARD' => 'shadcn-card',
            'TIPTAP' => 'tiptap',
        ];
    }
}
