<?php

namespace App\Policies;

use App\Models\ProblemCategory;
use App\Models\User;
use App\Services\ModelAuthorizer;

/**
 * Categories are one taxonomy shared by every padalinys (no tenant_id), so only those who edit
 * problems everywhere (`problems.*.*`) manage them. Like ResourceCategoryPolicy, it borrows the
 * owning model's permissions rather than seeding its own; `problems.read.*` is retired, so reading
 * the admin screen is tied to editing, not to browsing problems.
 */
class ProblemCategoryPolicy
{
    public function __construct(protected ModelAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'problems.update.*');
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'problems.update.*');
    }

    public function update(User $user, ProblemCategory $problemCategory): bool
    {
        return $this->authorizer->allows($user, 'problems.update.*');
    }

    public function delete(User $user, ProblemCategory $problemCategory): bool
    {
        return $this->authorizer->allows($user, 'problems.delete.*');
    }
}
