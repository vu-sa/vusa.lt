<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Layout variants for a news article, matching the `news.layout` column.
 *
 * The backing values used to be uppercase (`MODERN`) with the persisted lowercase string
 * hidden behind `label()`. Nothing in PHP used the enum, `News::LAYOUTS` was a second
 * definition of the same set, and spatie/typescript-transformer exported the *uppercase*
 * values to `resources/js/Types/enums.ts` — so any frontend comparison against `news.layout`
 * silently never matched. The backing value is now the value actually stored.
 */
enum NewsLayoutEnum: string
{
    use HasEnumHelpers;

    case MODERN = 'modern';
    case CLASSIC = 'classic';
    case IMMERSIVE = 'immersive';
    case HEADLINE = 'headline';

    public static function default(): self
    {
        return self::MODERN;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
