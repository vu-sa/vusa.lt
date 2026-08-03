<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Services\Typesense\TypesenseCollectionConfig;

enum SearchableModelEnum: string
{
    use HasEnumHelpers;

    case NEWS = 'NEWS';
    case PAGE = 'PAGE';
    case DOCUMENT = 'DOCUMENT';
    case CALENDAR = 'CALENDAR';
    case PUBLIC_INSTITUTION = 'PUBLIC_INSTITUTION';
    case PUBLIC_MEETING = 'PUBLIC_MEETING';
    case MEETING = 'MEETING';
    case AGENDA_ITEM = 'AGENDA_ITEM';
    case RESOURCE = 'RESOURCE';
    case INSTITUTION = 'INSTITUTION';
    case USER = 'USER';
    case DUTY = 'DUTY';

    public function label(): string
    {
        return match ($this) {
            self::NEWS => 'news',
            self::PAGE => 'page',
            self::DOCUMENT => 'document',
            self::CALENDAR => 'calendar',
            self::PUBLIC_INSTITUTION => 'public_institution',
            self::PUBLIC_MEETING => 'public_meeting',
            self::MEETING => 'meeting',
            self::AGENDA_ITEM => 'agenda_item',
            self::RESOURCE => 'resource',
            self::INSTITUTION => 'institution',
            self::USER => 'user',
            self::DUTY => 'duty',
        };
    }

    /**
     * Get all searchable model classes.
     * Delegates to TypesenseCollectionConfig as the single source of truth.
     *
     * @return array<class-string>
     */
    public static function getAllModelClasses(): array
    {
        return TypesenseCollectionConfig::getAllModelClasses();
    }

    /**
     * Get models that use Typesense (as opposed to database search)
     *
     * @return array<class-string>
     */
    public static function getTypesenseModelClasses(): array
    {
        // For now, all searchable models use Typesense
        // In the future, this could be filtered based on model configuration
        return self::getAllModelClasses();
    }
}
