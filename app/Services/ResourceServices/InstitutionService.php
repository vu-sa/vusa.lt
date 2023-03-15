<?php

namespace App\Services\ResourceServices;

use App\Models\Padalinys;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Auth;

class InstitutionService
{
    public static function getPadaliniaiForUpserts(ModelAuthorizer $authorizer)
    {
        // TODO: should be create or update
        // ! must be already authorized for this action
        if (!$authorizer->forUser(Auth::user())->checkAllRoleables('create.institution.all')) {
            return User::with('padaliniai:padaliniai.id,shortname')->find(Auth::user()->id)->padaliniai->unique();
        } else {
            return Padalinys::orderBy('shortname_vu')->get(['id', 'shortname']);
        }
    }
}
