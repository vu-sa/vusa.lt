<?php

namespace App\States\InstitutionCheckIns;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CheckInState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Active::class)
            ->allowTransitions([
                [Active::class, Expired::class],
                [Active::class, Invalidated::class],
                [Active::class, Withdrawn::class],
                [Active::class, Disputed::class],
                [Active::class, AdminSuppressed::class],
                [Disputed::class, Active::class],
                [Disputed::class, Withdrawn::class],
                [Disputed::class, AdminSuppressed::class],
                [AdminSuppressed::class, Active::class],
            ]);
    }
}
