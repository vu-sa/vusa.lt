<?php

namespace App\Actions;

use App\Actions\Responsibilities\AssignableDutyTypes;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetAttachableTypesForDuty
{
    public static function execute(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user === null ? new Collection : AssignableDutyTypes::for($user);
    }
}
