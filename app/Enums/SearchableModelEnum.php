<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self NEWS()
 * @method static self PAGE()
 * @method static self DOCUMENT()
 * @method static self CALENDAR()
 * @method static self PUBLIC_INSTITUTION()
 * @method static self PUBLIC_MEETING()
 * @method static self MEETING()
 * @method static self AGENDA_ITEM()
 * @method static self RESOURCE()
 */
final class SearchableModelEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'NEWS' => 'news',
            'PAGE' => 'page',
            'DOCUMENT' => 'document',
            'CALENDAR' => 'calendar',
            'PUBLIC_INSTITUTION' => 'public_institution',
            'PUBLIC_MEETING' => 'public_meeting',
            'MEETING' => 'meeting',
            'AGENDA_ITEM' => 'agenda_item',
            'RESOURCE' => 'resource',
        ];
    }

    /**
     * Get all searchable model classes
     */
    public static function getAllModelClasses(): array
    {
        return [
            \App\Models\News::class,
            \App\Models\Page::class,
            \App\Models\Document::class,
            \App\Models\Calendar::class,
            \App\Models\PublicInstitution::class,
            \App\Models\PublicMeeting::class,
            \App\Models\Meeting::class,
            \App\Models\Pivots\AgendaItem::class,
            \App\Models\Resource::class,
        ];
    }

    /**
     * Get models that use Typesense (as opposed to database search)
     */
    public static function getTypesenseModelClasses(): array
    {
        // For now, all searchable models use Typesense
        // In the future, this could be filtered based on model configuration
        return self::getAllModelClasses();
    }
}
