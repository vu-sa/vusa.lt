<?php

namespace App\Actions;

use App\Enums\Responsibility;
use App\Models\Institution;
use App\Models\User;
use App\Services\ResponsibilityResolver;
use Illuminate\Support\Collection;

class GetInstitutionManagers
{
    /**
     * People currently coordinating the institution's representatives (studentų atstovų
     * koordinavimas), resolved institution → type → padalinys.
     *
     * @return Collection<int, User>
     */
    public static function execute(Institution $institution): Collection
    {
        return app(ResponsibilityResolver::class)->usersFor(Responsibility::StudentRepCoordination, $institution);
    }
}
