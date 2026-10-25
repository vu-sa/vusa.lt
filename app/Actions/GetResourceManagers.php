<?php

namespace App\Actions;

use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Resource;
use App\Models\User;
use App\Services\ResponsibilityResolver;
use Illuminate\Support\Collection;

class GetResourceManagers
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Resource $resource): Collection
    {
        return app(ResponsibilityResolver::class)
            ->dutiesForTenant(ResponsibilityPurpose::ReservationHandling, $resource->tenant)
            ->loadMissing('current_users')
            ->flatMap(fn (Duty $duty) => $duty->current_users)
            ->unique('id')
            ->values();
    }
}
