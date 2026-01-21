<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Type;
use App\Models\User;
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

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            $types = Type::all();
        } else {
            $userWithDuties = User::query()->with('duties.roles.attachable_types')->find($user->id);
            $types = $userWithDuties?->duties
                ->flatten()->pluck('roles')->flatten()->pluck('attachable_types')->flatten()->unique('id')->values() ?? collect();
        }

        // filter types where model_type is App\Models\Duty
        $types = $types->filter(function ($type) {
            return $type->model_type === Duty::class;
        });

        // support collection to eloquent collection
        $types = Collection::make($types);

        return $types;
    }
}
