<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;

class GetInstitutionManagers {
    public static function execute(Institution $institution) {
        $institutionManagers = Duty::whereHas('institution.padalinys', function (Builder $query) use ($institution) {
            $query->where('id', $institution->padalinys_id);
        })->whereHas('roles.permissions', function (Builder $query) {
            $query->where('name', config('permission.institution_managership_indicating_permission'));
        })->with('users')->get()->pluck('users')->flatten()->unique('id')->values();

        return $institutionManagers;
    }
}