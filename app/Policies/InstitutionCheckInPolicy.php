<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;

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

    public function confirm(User $user, InstitutionCheckIn $checkIn): bool
    {
    // Members can confirm, but not the author themselves
    return $user->id !== $checkIn->user_id && $this->isMember($user, $checkIn->institution);
    }

    public function withdraw(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $user->id === $checkIn->user_id;
    }

    public function dispute(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $this->isMember($user, $checkIn->institution);
    }

    public function resolve(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $user->id === $checkIn->user_id;
    }

    public function suppress(User $user, InstitutionCheckIn $checkIn): bool
    {
    return $this->isAdmin($user);
    }

    public function unsuppress(User $user, InstitutionCheckIn $checkIn): bool
    {
    return $this->isAdmin($user);
    }

    public function flag(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $this->isMember($user, $checkIn->institution) || $this->isAdmin($user);
    }

    private function isMember(User $user, Institution $institution): bool
    {
        // user has any current duty in the institution
        return $institution->users()->whereKey($user->getKey())->exists();
    }

    private function isAdmin(User $user): bool
    {
        // Uses configured permission that indicates institution managers
        $perm = config('permission.institution_managership_indicating_permission');
        return $user->can($perm);
    }
}
