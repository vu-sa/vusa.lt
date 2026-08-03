<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum PageLayoutEnum: string
{
    use HasEnumHelpers;

    case DEFAULT = 'DEFAULT';
    case WIDE = 'WIDE';
    case FOCUSED = 'FOCUSED';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => 'default',
            self::WIDE => 'wide',
            self::FOCUSED => 'focused',
        };
    }
}
