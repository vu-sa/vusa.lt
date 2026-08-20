<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Layout variants for a static page, matching the `pages.layout` column.
 *
 * See NewsLayoutEnum for why the backing values are lowercase.
 */
enum PageLayoutEnum: string
{
    use HasEnumHelpers;

    case DEFAULT = 'default';
    case WIDE = 'wide';
    case FOCUSED = 'focused';

    public static function default(): self
    {
        return self::DEFAULT;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
