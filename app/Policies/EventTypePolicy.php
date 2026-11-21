<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy for EventType model authorization
 */
class EventTypePolicy extends ModelPolicy
{
    /**
     * Event types are global entities that don't belong to specific tenants.
     * Users with the appropriate global permission can manage all of them.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'eventTypes.read.*');
    }

    #[\Override]
    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'eventTypes.create.*');
    }

    #[\Override]
    public function view(User $user, Model $eventType): bool
    {
        return $this->authorizer->allows($user, 'eventTypes.read.*');
    }

    #[\Override]
    public function update(User $user, Model $eventType): bool
    {
        return $this->authorizer->allows($user, 'eventTypes.update.*');
    }

    #[\Override]
    public function delete(User $user, Model $eventType): bool
    {
        return $this->authorizer->allows($user, 'eventTypes.delete.*');
    }
}
