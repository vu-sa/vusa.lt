<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Hero presentation variants for a calendar event page, matching the
 * `calendar.hero_style` column. See PageLayoutEnum for why the backing
 * values are lowercase.
 */
enum CalendarHeroStyleEnum: string
{
    use HasEnumHelpers;

    case CARD = 'card';
    case SPLIT = 'split';
    case MINIMAL = 'minimal';

    public static function default(): self
    {
        return self::CARD;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
