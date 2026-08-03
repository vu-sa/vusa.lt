<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
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

    /**
     * Determine whether the user can update the model.
     *
     * Resources belong to a single tenant, so we use hasManyTenants=false
     */
    #[\Override]
    public function update(User $user, Model $resource): bool
    {
        return $this->commonChecker($user, $resource, CRUDEnum::UPDATE->label(), null, false);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Resources belong to a single tenant, so we use hasManyTenants=false
     */
    #[\Override]
    public function delete(User $user, Model $resource): bool
    {
        return $this->commonChecker($user, $resource, CRUDEnum::DELETE->label(), null, false);
    }
}
