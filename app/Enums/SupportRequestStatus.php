<?php

namespace App\Enums;

enum SupportRequestStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => __('Naujas'),
            self::Reviewing => __('Peržiūrima'),
            self::Planned => __('Suplanuota'),
            self::InProgress => __('Vykdoma'),
            self::Done => __('Išspręsta'),
            self::Declined => __('Atmesta'),
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Reviewing => 'amber',
            self::Planned => 'purple',
            self::InProgress => 'indigo',
            self::Done => 'green',
            self::Declined => 'destructive',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Done || $this === self::Declined;
    }
}
