<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Category;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Category model authorization
 */
class CategoryPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::CATEGORY()->label);
    }

    /**
     * Categories are global entities that don't belong to specific tenants
     * Users with the appropriate global permission can manage all categories
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables('categories.read.*');
    }

    public function create(User $user): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables('categories.create.*');
    }

    public function view(User $user, Model $category): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables('categories.read.*');
    }

    public function update(User $user, Model $category): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables('categories.update.*');
    }

    public function delete(User $user, Model $category): bool
    {
        return $this->authorizer->forUser($user)->checkAllRoleables('categories.delete.*');
    }
}
