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
     * Get a color class for the notification category.
     *
     * @todo PR 6.5 (.ai/redesign/admin) remaps this onto the `--cat-*` categorical tokens —
     * these bare hue names collide with the six status roles from PR 2.2 (Task orange ==
     * attention, System red == danger, Duty amber == the dark-mode brand) and are not what the
     * new colour system means by "status". Do not "fix" this into `--status-*` in the meantime.
     */
    public function color(): string
    {
        return match ($this) {
            self::Comment => 'blue',
            self::Task => 'orange',
            self::Reservation => 'purple',
            self::Meeting => 'green',
            self::Registration => 'cyan',
            self::User => 'gray',
            self::Duty => 'amber',
            self::System => 'red',
            self::News => 'indigo',
            self::Calendar => 'teal',
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
