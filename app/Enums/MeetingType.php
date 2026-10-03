<?php

namespace App\Enums;

enum MeetingType: string
{
    case InPerson = 'in-person';
    case Remote = 'remote';
    case Email = 'email';

    /**
     * Get the localized label for the meeting type.
     */
    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::InPerson => $locale === 'en' ? 'In-person Meeting' : 'Gyvas posėdis',
            self::Remote => $locale === 'en' ? 'Remote Meeting' : 'Nuotolinis posėdis',
            self::Email => $locale === 'en' ? 'E-meeting (via email)' : 'Sprendimas el. paštu',
        };
    }

    /**
     * Check if this type requires only date input (no time).
     * Email meetings are deadline-based and don't need specific time.
     */
    public function isDateOnly(): bool
    {
        return $this === self::Email;
    }

    /**
     * Get all meeting types as an array for frontend.
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label($locale),
                'isDateOnly' => $type->isDateOnly(),
            ],
            self::cases()
        );
    }
}
