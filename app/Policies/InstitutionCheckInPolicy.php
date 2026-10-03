<?php

namespace App\Policies;

use App\Enums\Responsibility;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\ResponsibilityResolver;

class InstitutionCheckInPolicy
{
    public function __construct(
        private readonly ModelAuthorizer $authorizer,
        private readonly ResponsibilityResolver $responsibilities,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'institutions.update.padalinys')
            || $this->responsibilities->holdsAnywhere($user, Responsibility::StudentRepCoordination);
    }

    public function view(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $this->isMember($user, $checkIn->institution) || $this->administers($user, $checkIn->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $this->isMember($user, $institution) || $this->administers($user, $institution);
    }

    public function delete(User $user, InstitutionCheckIn $checkIn): bool
    {
        return $user->id === $checkIn->user_id || $this->administers($user, $checkIn->institution);
    }

    public function deleteAll(User $user, Institution $institution): bool
    {
        return $this->administers($user, $institution);
    }

    private function isMember(User $user, Institution $institution): bool
    {
        return $institution->users()->whereKey($user->getKey())->exists();
    }

    /**
     * Whoever may edit the institution in its padalinys, or coordinates it.
     */
    private function administers(User $user, Institution $institution): bool
    {
        $scope = $this->authorizer->scope($user, 'institutions.update.padalinys');

        if ($scope->isAllScope || $scope->tenantIds()->contains($institution->tenant_id)) {
            return true;
        }

        return $this->responsibilities->isResponsibleFor($user, Responsibility::StudentRepCoordination, $institution);
    }
}
