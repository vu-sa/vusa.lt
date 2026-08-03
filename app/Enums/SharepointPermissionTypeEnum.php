<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum SharepointPermissionTypeEnum: string
{
    use HasEnumHelpers;

    case VIEW = 'VIEW';
    case EDIT = 'EDIT';
    case OWNER = 'OWNER';

    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'view',
            self::EDIT => 'edit',
            self::OWNER => 'owner',
        };
    }
}
