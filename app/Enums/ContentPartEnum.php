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
 * @method static self SOCIAL_EMBED()
 * @method static self FLOW_GRAPH()
 * @method static self NUMBER_STAT_SECTION()
 * @method static self NEWS()
 * @method static self CALENDAR()
 * @method static self CONTENT_GRID()
 * @method static self TEXT_BOX()
 * @method static self CAROUSEL_SLIDE_DECK()
 * @method static self CARD_STACK()
 * @method static self PHOTO_GALLERY()
 * @method static self LINK_LIST()
 * @method static self EVENT_LIST()
 * @method static self PERSON_QUOTE()
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
            'SOCIAL_EMBED' => 'social-embed',
            'FLOW_GRAPH' => 'flow-graph',
            'NUMBER_STAT_SECTION' => 'number-stat-section',
            'NEWS' => 'news',
            'CALENDAR' => 'calendar',
            'CONTENT_GRID' => 'content-grid',
            'TEXT_BOX' => 'text-box',
            'CAROUSEL_SLIDE_DECK' => 'carousel-slide-deck',
            'CARD_STACK' => 'card-stack',
            'PHOTO_GALLERY' => 'photo-gallery',
            'LINK_LIST' => 'link-list',
            'EVENT_LIST' => 'event-list',
            'PERSON_QUOTE' => 'person-quote',
        ];
    }
}
