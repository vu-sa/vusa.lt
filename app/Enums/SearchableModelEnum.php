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
