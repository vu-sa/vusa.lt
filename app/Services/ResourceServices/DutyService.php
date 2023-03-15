<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Services\ModelAuthorizer;

class DutyService
{
    public static function getInstitutionsForUpserts(ModelAuthorizer $authorizer)
    {
        return Institution::select('id', 'name', 'alias', 'padalinys_id')
            ->when(! $authorizer->forUser(request()->user())->checkAllRoleables('duties.create.all'),
                function ($query) {
                    $query->whereIn('padalinys_id', auth()->user()->padaliniai->pluck('id'));
                })
            ->with('padalinys:id,shortname')
            ->get();
    }
}
