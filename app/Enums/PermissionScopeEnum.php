<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self own()
 * @method static self padalinys()
 * @method static self all()
 */

 class PermissionScopeEnum extends Enum {
    protected static function values(): array
    {
        return [
            'own' => 'own',
            'padalinys' => 'padalinys',
            'all' => '*',
        ];
    }
 }