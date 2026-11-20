<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResourcePolicy extends ModelPolicy
{
    /**
     * Anyone can view the resource listing
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * Resources are visible to all authenticated users across tenants so they can
     * be discovered and reserved through the unified admin search.
     */
    #[\Override]
    public function view(User $user, Model $resource): bool
    {
        return true;
    }
}
