<?php

namespace App\Enums;

/**
 * Notification categories used for organizing and filtering notifications.
 * Each category maps to a model icon key for frontend display consistency.
 *
 * @typescript
 */
enum NotificationCategory: string
{
    case Comment = 'comment';
    case Task = 'task';
    case Reservation = 'reservation';
    case Meeting = 'meeting';
    case Registration = 'registration';
    case User = 'user';
    case Duty = 'duty';
    case System = 'system';
    case News = 'news';
    case Calendar = 'calendar';

    /**
     * Get the ModelEnum key for icon mapping on frontend.
     */
    public function modelEnumKey(): string
    {
        return match ($this) {
            self::Comment => 'COMMENT',
            self::Task => 'TASK',
            self::Reservation => 'RESERVATION',
            self::Meeting => 'MEETING',
            self::Registration => 'FORM',
            self::User => 'USER',
            self::Duty => 'DUTY',
            self::System => 'TENANT', // Using tenant icon for system-wide notifications
            self::News => 'NEWS',
            self::Calendar => 'CALENDAR',
        };
    }

    /**
     * The categorical colour token (`cat-1`…`cat-8`, or `neutral`) marking this category.
     *
     * Small marks only, always beside a label: these are never status colours.
     */
    public function color(): string
    {
        return match ($this) {
            self::Comment => 'cat-2',
            self::Task => 'cat-6',
            self::Reservation => 'cat-4',
            self::Meeting => 'cat-8',
            self::Registration => 'cat-1',
            self::Duty => 'cat-7',
            self::News => 'cat-5',
            self::Calendar => 'cat-3',
            self::User, self::System => 'neutral',
        };
    }

    /**
     * Light-mode hex of color() for email, which cannot read CSS variables. Mirrors
     * `--cat-*` and `--status-neutral` in resources/css/theme/base-tokens.css.
     */
    public function colorHex(): string
    {
        return match ($this->color()) {
            'cat-1' => '#007c7c',
            'cat-2' => '#007598',
            'cat-3' => '#4966a8',
            'cat-4' => '#73599e',
            'cat-5' => '#8f4e82',
            'cat-6' => '#9c522e',
            'cat-7' => '#7d6700',
            'cat-8' => '#007d5f',
            default => '#58554f',
        };
    }

    /**
     * Get the translation key for this category.
     */
    public function labelKey(): string
    {
        return 'notifications.categories.'.$this->value;
    }

    /**
     * Get all categories as options for settings.
     *
     * @return array<string, array{value: string, modelEnumKey: string, color: string}>
     */
    public static function toOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = [
                'value' => $case->value,
                'modelEnumKey' => $case->modelEnumKey(),
                'color' => $case->color(),
            ];
        }

        return $options;
    }
}
