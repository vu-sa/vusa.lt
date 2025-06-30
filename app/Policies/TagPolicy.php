<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TAG()->label);
    }

    /**
     * Tags are global entities that don't belong to specific tenants
     * Users with the appropriate global permission can manage all tags
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables("tags.read.*");
    }

    public function create(User $user): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables("tags.create.*");
    }

    public function view(User $user, Model $tag): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables("tags.read.*");
    }

    public function update(User $user, Model $tag): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables("tags.update.*");
    }

    public function delete(User $user, Model $tag): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables("tags.delete.*");
    }
}
