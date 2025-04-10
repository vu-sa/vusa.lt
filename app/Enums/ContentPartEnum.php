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
 * @method static self HERO()
 * @method static self SPOTIFY_EMBED()
 * @method static self FLOW_GRAPH()
 * @method static self NUMBER_STAT_SECTION()
 * @method static self NEWS()
 * @method static self CALENDAR()
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
            'HERO' => 'hero',
            'SPOTIFY_EMBED' => 'spotify-embed',
            'FLOW_GRAPH' => 'flow-graph',
            'NUMBER_STAT_SECTION' => 'number-stat-section',
            'NEWS' => 'news',
            'CALENDAR' => 'calendar',
        ];
    }
}
