<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for EventType model authorization
 */
class EventTypePolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::EVENT_TYPE->label());
    }

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
