<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum SharepointScopeEnum: string
{
    use HasEnumHelpers;

    case ANONYMOUS = 'ANONYMOUS';
    case ORGANIZATION = 'ORGANIZATION';
    case USERS = 'USERS';

    public function label(): string
    {
        return match ($this) {
            self::ANONYMOUS => 'anonymous',
            self::ORGANIZATION => 'organization',
            self::USERS => 'users',
        };
    }
}
