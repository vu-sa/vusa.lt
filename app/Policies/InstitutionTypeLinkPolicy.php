<?php

namespace App\Policies;

use App\Models\InstitutionTypeLink;
use App\Models\User;
use App\Services\ModelAuthorizer;

/**
 * Links grant data access across institutions, so managing them is global-scope only.
 */
class InstitutionTypeLinkPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'relationships.create.*');
    }

    public function update(User $user, InstitutionTypeLink $link): bool
    {
        return $this->authorizer->allows($user, 'relationships.update.*');
    }

    public function delete(User $user, InstitutionTypeLink $link): bool
    {
        return $this->authorizer->allows($user, 'relationships.delete.*');
    }
}
