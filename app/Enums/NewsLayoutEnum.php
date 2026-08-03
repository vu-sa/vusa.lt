<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum NewsLayoutEnum: string
{
    use HasEnumHelpers;

    case MODERN = 'MODERN';
    case CLASSIC = 'CLASSIC';
    case IMMERSIVE = 'IMMERSIVE';
    case HEADLINE = 'HEADLINE';

    public function label(): string
    {
        return match ($this) {
            self::MODERN => 'modern',
            self::CLASSIC => 'classic',
            self::IMMERSIVE => 'immersive',
            self::HEADLINE => 'headline',
        };
    }
}
