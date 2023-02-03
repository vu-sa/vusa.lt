<?php

namespace App\Services\ResourceServices;

use App\Models\Relationship;
use App\Models\Type;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\AllowedRelationshipablesEnum;
use App\Models\Padalinys;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Auth;

class InstitutionService
{
    public static function getPadaliniaiForUpserts(ModelAuthorizer $authorizer) {

        // TODO: should be create or update
        if ($authorizer->forUser(Auth::user())->checkAllRoleables("create.institution.*")) {
            $padaliniai = Padalinys::orderBy('shortname_vu')->get(['id', 'shortname']);
        } else {
            // TODO: bet nepatikrina, ar tuose padaliniuose turi institution.padalinys teises
            $padaliniai = User::with('padaliniai:padaliniai.id,shortname')->find(Auth::user()->id)->padaliniai->unique();
        }

        return $padaliniai;
    }
}