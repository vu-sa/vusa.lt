<?php

namespace App\Enums;

/**
 * How a person comes to hold a duty (or how an institution's members are
 * appointed). Used for the "Appointment" panel on the duty show page.
 */
enum DutySelectionMethod: string
{
    case Elected = 'elected';
    case Delegated = 'delegated';
    case Appointed = 'appointed';

    /**
     * Get the localized label for the selection method.
     */
    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Elected => $locale === 'en' ? 'Elected' : 'Renkama',
            self::Delegated => $locale === 'en' ? 'Delegated' : 'Deleguojama',
            self::Appointed => $locale === 'en' ? 'Appointed' : 'Skiriama',
        };
    }

    /**
     * Get all selection methods as an array for frontend selects.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $method) => [
                'value' => $method->value,
                'label' => $method->label($locale),
            ],
            self::cases()
        );
    }
}
