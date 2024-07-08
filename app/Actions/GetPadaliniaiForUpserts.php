<?php

namespace App\Actions;

use App\Models\Padalinys;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Auth;

class GetPadaliniaiForUpserts
{
    // e.g. 'institutions.create.all'
    public static function execute(string $permission, ModelAuthorizer $authorizer): \Illuminate\Support\Collection
    {
        // ! must be already authorized for this action
        if (! $authorizer->forUser(Auth::user())->checkAllRoleables($permission)) {
            return User::query()->with('padaliniai:padaliniai.id,shortname')->find(Auth::user()->id)->padaliniai->unique()->map(
                function ($padalinys) {
                    return [
                        'id' => $padalinys->id,
                        'shortname' => __($padalinys->shortname),
                    ];
                }
            );
        }

        return Padalinys::query()->orderBy('shortname_vu')->get(['id', 'shortname'])->map(
            function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname' => __($padalinys->shortname),
                ];
            }
        );
    }
}
