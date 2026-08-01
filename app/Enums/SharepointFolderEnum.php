<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum SharepointFolderEnum: string
{
    use HasEnumHelpers;

    case GENERAL = 'GENERAL';
    case PADALINIAI = 'PADALINIAI';
    case TYPES = 'TYPES';
    case INSTITUTIONS = 'INSTITUTIONS';
    case MEETINGS = 'MEETINGS';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::PADALINIAI => 'Padaliniai',
            self::TYPES => 'Types',
            self::INSTITUTIONS => 'Institutions',
            self::MEETINGS => 'Meetings',
        };
    }
}
