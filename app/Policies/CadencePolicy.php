<?php

namespace App\Policies;

use App\Models\Cadence;
use App\Models\Institution;
use App\Models\User;
use App\Settings\SettingsSettings;

/**
 * Cadences carry no permission matrix of their own, so they authorize against what owns
 * them: the global ladder is settings config, an override belongs to its institution.
 *
 * Same shape as ReservationResourceController → Reservation — delegating beats inventing
 * unseeded `cadences.*` permissions that would lock out everyone but super admins.
 */
class CadencePolicy
{
    public function viewAny(User $user): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($user);
    }

    public function update(User $user, Cadence $cadence): bool
    {
        return $this->mayManage($user, $cadence->institution_id);
    }

    public function delete(User $user, Cadence $cadence): bool
    {
        return $this->mayManage($user, $cadence->institution_id);
    }

    /**
     * Create is checked against the institution named in the request, because there is no
     * model yet — the caller must pass the same id it is about to write.
     */
    public function createFor(User $user, ?string $institutionId): bool
    {
        return $this->mayManage($user, $institutionId);
    }

    private function mayManage(User $user, ?string $institutionId): bool
    {
        if ($institutionId === null) {
            return app(SettingsSettings::class)->canUserManageSettings($user);
        }

        $institution = Institution::find($institutionId);

        return $institution !== null && $user->can('update', $institution);
    }
}
