<?php

namespace App\Enums;

enum AgendaItemType: string
{
    case Voting = 'voting';
    case Informational = 'informational';
    case Deferred = 'deferred';

    /**
     * Get the localized label for the agenda item type.
     */
    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Voting => $locale === 'en' ? 'Voting' : 'Balsavimas',
            self::Informational => $locale === 'en' ? 'Informational' : 'Informacinis',
            self::Deferred => $locale === 'en' ? 'Deferred' : 'Atidėtas',
        };
    }

    /**
     * Get badge color for the type.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Voting => 'green',
            self::Informational => 'blue',
            self::Deferred => 'gray',
        };
    }

    /**
     * Get all types as an array for frontend.
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label($locale),
                'badgeColor' => $type->badgeColor(),
            ],
            self::cases()
        );
    }
}
