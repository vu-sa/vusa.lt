<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Resource;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResourcePolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::RESOURCE->label());
    }

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
