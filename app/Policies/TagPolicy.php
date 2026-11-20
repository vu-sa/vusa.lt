<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TagPolicy extends ModelPolicy
{
    /**
     * Tags are global entities that don't belong to specific tenants
     * Users with the appropriate global permission can manage all tags
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'tags.read.*');
    }

    #[\Override]
    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'tags.create.*');
    }

    #[\Override]
    public function view(User $user, Model $tag): bool
    {
        return $this->authorizer->allows($user, 'tags.read.*');
    }

    #[\Override]
    public function update(User $user, Model $tag): bool
    {
        return $this->authorizer->allows($user, 'tags.update.*');
    }

    #[\Override]
    public function delete(User $user, Model $tag): bool
    {
        return $this->authorizer->allows($user, 'tags.delete.*');
    }
}
