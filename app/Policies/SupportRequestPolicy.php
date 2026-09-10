<?php

namespace App\Policies;

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\ModelAuthorizer;

class SupportRequestPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    /**
     * Determine whether the user can view any models (access the admin queue).
     */
    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupportRequest $supportRequest): bool
    {
        if ($this->isManager($user)
            || $supportRequest->created_by === $user->id
            || $supportRequest->assigned_to === $user->id
        ) {
            return true;
        }

        if ($supportRequest->visibility === SupportRequestVisibility::Public) {
            return true;
        }

        if ($supportRequest->visibility !== SupportRequestVisibility::Roles) {
            return false;
        }

        $roleIds = $supportRequest->roles()->pluck('roles.id');

        return $user->roles()->whereIn('roles.id', $roleIds)->exists()
            || $user->current_duties()->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model content.
     */
    public function update(User $user, SupportRequest $supportRequest): bool
    {
        return $this->isManager($user)
            || ($supportRequest->created_by === $user->id && $supportRequest->status === SupportRequestStatus::New);
    }

    /**
     * Determine whether the user can change the status of the support request.
     */
    public function updateStatus(User $user, SupportRequest $supportRequest): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can assign the support request to a user.
     */
    public function assign(User $user, SupportRequest $supportRequest): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupportRequest $supportRequest): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupportRequest $supportRequest): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupportRequest $supportRequest): bool
    {
        return $this->authorizer->allows($user, 'supportRequests.forceDelete.*');
    }

    public function isManager(User $user): bool
    {
        return $this->authorizer->allows($user, 'supportRequests.read.*')
            || $this->authorizer->allows($user, 'supportRequests.update.*');
    }
}
