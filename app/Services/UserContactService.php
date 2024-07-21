<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class UserContactService
{
    // public static function getInstitutionsFromTenantAndType(Tenant $tenant, Type $type): Builder
    // {
    //     $institutionBuilder = Institution::with(['duties.users'])
    //             ->orderBy('name')
    //             ->where([['tenant_id', '=', $tenant->id]])
    //             ->whereHas('types', function (Builder $query) use ($type) {
    //                 $query->whereIn('slug', $type->getDescendantsAndSelf()->pluck('slug'));
    //             });

    //     return $institutionBuilder;
    // }
}
