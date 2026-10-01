<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\DutyType;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection;

class GetAttachableTypesForDuty
{
    public static function execute(): Collection
    {
        if (auth()->guest()) {
            return new Collection;
        }

        // get all attachable types for the current user
        $types = [];

        /** @var User $user */
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $types = DutyType::all();
        } else {
            $userWithDuties = User::query()->with('duties.roles.attachable_types')->find($user->id);
            $types = $userWithDuties?->duties
                ->flatten()->pluck('roles')->flatten()->pluck('attachable_types')->flatten()->unique('id')->values() ?? collect();
        }

        // filter types where model_type is App\Models\Duty
        

        // support collection to eloquent collection
        $types = Collection::make($types);

        return $types;
    }
}
