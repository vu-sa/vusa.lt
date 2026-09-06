<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Layout variants for a static page, matching the `pages.layout` column.
 *
 * The backing value is the string actually stored in `pages.layout` — an earlier uppercase-cased
 * variant meant every frontend comparison against it silently never matched.
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
