<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum PermissionScopeEnum: string
{
    use HasEnumHelpers;

    case OWN = 'OWN';
    case PADALINYS = 'PADALINYS';
    case ALL = 'ALL';

    public function label(): string
    {
        return match ($this) {
            self::OWN => 'own',
            self::PADALINYS => 'padalinys',
            self::ALL => '*',
        };
    }
}
