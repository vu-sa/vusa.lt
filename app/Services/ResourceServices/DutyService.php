<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Services\ModelAuthorizer;

class DutyService
{
    public static function getInstitutionsForUpserts(ModelAuthorizer $authorizer)
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $authorizer->forUser(request()->user())->checkAllRoleables('duties.create.all'),
                function ($query) {
                    $query->whereIn('tenant_id', auth()->user()->tenants->pluck('id'));
                })
            ->with('tenant:id,shortname')
            ->get();
    }
}
