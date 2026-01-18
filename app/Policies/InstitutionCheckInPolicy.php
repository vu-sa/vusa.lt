<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Settings\AtstovavimasSettings;

class InstitutionCheckInPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $this->isMember($user, $checkIn->institution) || $this->isAdmin($user);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $this->isMember($user, $institution) || $this->isAdmin($user);
    }

    public function delete(User $user, InstitutionCheckIn $checkIn): bool
    {
        // Author can delete their own, or admin can delete any
        return $user->id === $checkIn->user_id || $this->isAdmin($user);
    }

    private function isMember(User $user, Institution $institution): bool
    {
        // user has any current duty in the institution
        return $institution->users()->whereKey($user->getKey())->exists();
    }

    private function isAdmin(User $user): bool
    {
        // Uses configured role that indicates institution managers
        $settings = app(AtstovavimasSettings::class);

        return $settings->userIsInstitutionManager($user);
    }
}
