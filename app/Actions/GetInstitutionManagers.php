<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Builder;

class GetInstitutionManagers
{
    /**
     * Get all users who are institution managers for the given institution's tenant.
     *
     * Institution managers are identified by having a duty with the configured
     * institution_manager_role_id (from AtstovavimasSettings) in the same tenant.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public static function execute(Institution $institution): \Illuminate\Support\Collection
    {
        $settings = app(AtstovavimasSettings::class);
        $managerRoleId = $settings->getInstitutionManagerRoleId();

        if (! $managerRoleId) {
            return collect();
        }

        $institutionManagers = Duty::whereHas('institution.tenant', function (Builder $query) use ($institution) {
            $query->where('id', $institution->tenant_id);
        })->whereHas('roles', function (Builder $query) use ($managerRoleId) {
            $query->where('id', $managerRoleId);
        })->with('current_users')->get()->pluck('current_users')->flatten()->unique('id')->values();

        /** @var \Illuminate\Support\Collection<int, \App\Models\User> $result */
        $result = $institutionManagers;

        return $result;
    }
}
