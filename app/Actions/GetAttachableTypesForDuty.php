<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Type;
use App\Models\User;

//
class GetAttachableTypesForDuty
{
    public static function execute()
    {

        // get all attachable types for the current user

        $types = [];

        if (auth()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $types = Type::all();
        } else {
            $types = User::query()->with('duties.roles.attachable_types')->find(auth()->user()->id)->duties
                ->flatten()->pluck('roles')->flatten()->pluck('attachable_types')->flatten()->unique('id')->values();
        }

        // filter types where model_type is App\Models\Duty
        $types = $types->filter(function ($type) {
            return $type->model_type === Duty::class;
        });

        return $types;
    }
}
