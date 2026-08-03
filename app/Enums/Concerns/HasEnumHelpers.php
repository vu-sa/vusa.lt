<?php

namespace App\Enums\Concerns;

/**
 * Restores the array-conversion helpers that spatie/laravel-enum used to provide
 * (toValues()/toLabels()/toArray()) for native PHP backed enums.
 *
 * Requires the using enum to define an instance `label(): string` method.
 */
trait HasEnumHelpers
{
    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $case) => $case->label(), self::cases());
    }

    /**
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_combine(self::values(), self::labels());
    }
}
