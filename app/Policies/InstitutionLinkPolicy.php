<?php

namespace App\Policies;

use App\Models\InstitutionLink;
use App\Models\User;
use App\Services\ModelAuthorizer;

/**
 * Links grant data access across institutions, so managing them is global-scope only.
 */
class InstitutionLinkPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'relationships.create.*');
    }

    public function update(User $user, InstitutionLink $link): bool
    {
        return $this->authorizer->allows($user, 'relationships.update.*');
    }

    public function delete(User $user, InstitutionLink $link): bool
    {
        return $this->authorizer->allows($user, 'relationships.delete.*');
    }
}
